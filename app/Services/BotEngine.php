<?php

namespace App\Services;

use App\Models\BotContact;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Plan;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Deterministic, DB-driven Spanish WhatsApp SALES bot for NH's gym membership line.
 *
 * It is a finite-state machine keyed on BotContact->step:
 *   new → choosing → confirming → done   (+ the cross-cutting "human" handoff state)
 *
 * Every reply is fixed copy, kept in clearly-labeled private methods so it is trivially
 * editable per client. The final sale confirmation is gated by trial_locked() (see
 * config/trial.php) so a trial deploy captures leads without closing real sales.
 */
class BotEngine
{
    // ---- finite-state-machine steps -------------------------------------
    private const STEP_NEW = 'new';
    private const STEP_CHOOSING = 'choosing';
    private const STEP_CONFIRMING = 'confirming';
    private const STEP_DONE = 'done';
    private const STEP_HUMAN = 'human';

    public function __construct(private GatewayClient $gateway) {}

    public function handle(string $from, ?string $fromName, string $text): void
    {
        $contact = BotContact::firstOrCreate(['phone' => $from]);

        // Keep the contact's display name fresh (WhatsApp pushName) without clobbering it with null.
        if (filled($fromName) && $contact->name !== $fromName) {
            $contact->name = $fromName;
            $contact->save();
        }

        $normalized = Str::lower(trim($text));

        // ESCALATION (any step): the client wants a real person → hand off and go silent.
        if ($this->wantsHuman($normalized)) {
            if ($contact->step !== self::STEP_HUMAN) {
                $this->setStep($contact, self::STEP_HUMAN);
                $this->reply($from, $this->copyHandoff());
            }

            return;
        }

        // A human has taken over this chat → the bot stays completely silent.
        if ($contact->step === self::STEP_HUMAN) {
            return;
        }

        // The literal word "menu"/"menú" resets the funnel from anywhere.
        if (in_array($normalized, ['menu', 'menú'], true)) {
            $this->setStep($contact, self::STEP_NEW);
        }

        match ($contact->step) {
            self::STEP_CHOOSING => $this->onChoosing($contact, $from, $fromName, $normalized),
            self::STEP_CONFIRMING => $this->onConfirming($contact, $from, $fromName, $normalized),
            self::STEP_DONE => $this->onDone($contact, $from),
            default => $this->onNew($contact, $from), // STEP_NEW / first contact / unknown
        };
    }

    // ---- states ---------------------------------------------------------

    /** Greet by name and present the active plans, then wait for a choice. */
    private function onNew(BotContact $contact, string $from): void
    {
        $plans = $this->activePlans();
        if ($plans->isEmpty()) {
            $this->reply($from, $this->copyNoPlans());

            return;
        }

        $this->setStep($contact, self::STEP_CHOOSING);
        $this->reply($from, $this->copyGreeting($contact->name).$this->planList($plans).$this->copyAskChoice());
    }

    /** Match the reply to a plan (by list number or fuzzy name); create the Pedido and ask to confirm. */
    private function onChoosing(BotContact $contact, string $from, ?string $fromName, string $text): void
    {
        $plans = $this->activePlans();
        if ($plans->isEmpty()) {
            $this->reply($from, $this->copyNoPlans());

            return;
        }

        $plan = $this->matchPlan($plans, $text);
        if (! $plan) {
            $this->reply($from, $this->copyNoMatch().$this->planList($plans).$this->copyAskChoice());

            return;
        }

        Pedido::create([
            'bot_contact_id' => $contact->id,
            'plan_id' => $plan->id,
            'cliente' => $fromName ?: $contact->name,
            'telefono' => $from,
            'estado' => 'nuevo',
        ]);

        $data = $contact->data ?? [];
        $data['plan_id'] = $plan->id;
        $contact->data = $data;
        $contact->step = self::STEP_CONFIRMING;
        $contact->save();

        $this->reply($from, $this->copyConfirmPrompt($plan));
    }

    /** Affirmative → confirm the Pedido and capture the buyer; negative → back to choosing. */
    private function onConfirming(BotContact $contact, string $from, ?string $fromName, string $text): void
    {
        if ($this->isYes($text)) {
            $pedido = $this->pendingPedido($contact);

            Cliente::updateOrCreate(
                ['telefono' => $from],
                ['nombre' => $fromName ?: $contact->name],
            );

            // Confirming a sign-up is the money-making step: while the trial is locked, capture the
            // lead (allowed) but don't mark the sale as officially confirmed/closed.
            if (trial_locked()) {
                if ($pedido) {
                    $pedido->update(['estado' => 'pendiente_pago']);
                }

                $this->setStep($contact, self::STEP_DONE);
                $this->reply($from, $this->copyConfirmedLocked($pedido?->plan));

                return;
            }

            if ($pedido) {
                $pedido->update(['estado' => 'confirmado']);
            }

            $this->setStep($contact, self::STEP_DONE);
            $this->reply($from, $this->copyConfirmed());

            return;
        }

        if ($this->isNo($text)) {
            $this->setStep($contact, self::STEP_CHOOSING);
            $plans = $this->activePlans();
            $this->reply($from, $this->copyChangedMind().$this->planList($plans).$this->copyAskChoice());

            return;
        }

        // Ambiguous reply → re-ask for an explicit yes/no.
        $this->reply($from, $this->copyConfirmRetry());
    }

    /** Order already registered → polite close; "menu" (handled upstream) restarts the flow. */
    private function onDone(BotContact $contact, string $from): void
    {
        $this->reply($from, $this->copyAlreadyDone());
    }

    // ---- plan helpers ---------------------------------------------------

    /** @return Collection<int,Plan> */
    private function activePlans(): Collection
    {
        return Plan::where('activo', true)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    /** Match by 1-based list number first, then by fuzzy name (either direction). */
    private function matchPlan(Collection $plans, string $text): ?Plan
    {
        $text = trim($text);

        if ($text !== '' && ctype_digit($text)) {
            return $plans->values()->get(((int) $text) - 1);
        }

        foreach ($plans as $plan) {
            $name = Str::lower(trim($plan->nombre));
            if ($name !== '' && (Str::contains($text, $name) || Str::contains($name, $text))) {
                return $plan;
            }
        }

        return null;
    }

    private function pendingPedido(BotContact $contact): ?Pedido
    {
        $planId = $contact->data['plan_id'] ?? null;

        return $contact->pedidos()
            ->where('estado', 'nuevo')
            ->when($planId, fn ($q) => $q->where('plan_id', $planId))
            ->latest('id')
            ->first()
            ?? $contact->pedidos()->where('estado', 'nuevo')->latest('id')->first();
    }

    // ---- copy (editable Spanish strings) --------------------------------

    private function copyGreeting(?string $name): string
    {
        $greeting = $name ? "¡Hola, {$name}! 👋" : '¡Hola! 👋';

        return $greeting." Bienvenido a *".config('app.name')."* 💪, tu gimnasio de confianza.\n\n"
            ."Estas son nuestras membresías:\n\n";
    }

    private function planList(Collection $plans): string
    {
        $lines = $plans->values()->map(function (Plan $plan, int $i) {
            $line = ($i + 1).'. *'.$plan->nombre.'* — '.$this->formatPrice($plan->precio);
            if (filled($plan->descripcion)) {
                $line .= "\n   ".$plan->descripcion;
            }

            return $line;
        });

        return $lines->implode("\n\n");
    }

    private function copyAskChoice(): string
    {
        return "\n\n¿Cuál te late más? Respóndeme con el *número* o el *nombre* de la membresía. 🙂";
    }

    private function copyNoMatch(): string
    {
        return "No identifiqué esa membresía. 🤔 Estas son las disponibles:\n\n";
    }

    private function copyConfirmPrompt(Plan $plan): string
    {
        return '¡Buena elección! 💪 Elegiste *'.$plan->nombre.'* ('.$this->formatPrice($plan->precio).").\n\n"
            .'¿Confirmas tu inscripción? Responde *sí* para reservar tu lugar o *no* para elegir otra membresía.';
    }

    private function copyConfirmRetry(): string
    {
        return 'Para continuar, respóndeme *sí* para confirmar tu inscripción o *no* para elegir otra membresía. 🙂';
    }

    private function copyChangedMind(): string
    {
        return "Sin problema. 💪 Aquí están las membresías de nuevo:\n\n";
    }

    private function copyConfirmed(): string
    {
        return "¡Listo! ✅ Registramos tu inscripción. Un asesor te contactará en breve para completar tu pago y darte la bienvenida al equipo. 💪\n\n"
            ."Si quieres empezar de nuevo, escribe *menu*.";
    }

    private function copyConfirmedLocked(?Plan $plan): string
    {
        $membresia = $plan ? " a *{$plan->nombre}*" : '';

        return "¡Genial! 💪 Registramos tu inscripción{$membresia}.\n\n"
            .'🔒 '.trial_locked_message()."\n\n"
            ."Si quieres empezar de nuevo, escribe *menu*.";
    }

    private function copyAlreadyDone(): string
    {
        return "Ya registramos tu inscripción ✅ y un asesor te contactará pronto. 💪\n\n"
            ."Si quieres empezar de nuevo, escribe *menu*.";
    }

    private function copyNoPlans(): string
    {
        return 'Gracias por escribir 💪 En un momento un asesor te atiende personalmente.';
    }

    private function copyHandoff(): string
    {
        return '¡Claro que sí! 💪 Te paso con uno de nuestros coaches para que te atienda personalmente. '
            .'En breve te contactan. ¡Quedo al pendiente! 😊';
    }

    // ---- matchers (deterministic, ported from BotResponder STYLE) -------

    /** Affirmative confirmation (guards against explicit declines). Word-boundary matched so short
     *  tokens like "va"/"si" don't fire inside larger words ("nueva", "sitio"). */
    private function isYes(string $text): bool
    {
        if ($this->isNo($text)) {
            return false;
        }

        if (preg_match('/\b(s[ií]|sip|sale|va|dale|ok|okay|claro|listo|correcto|adelante|confirm\w*|acept\w*|procede)\b/u', $text)) {
            return true;
        }

        return Str::contains($text, [
            'de acuerdo', 'me late', 'por supuesto', 'está bien', 'esta bien', 'hágale', 'hagale', 'perfecto',
        ]);
    }

    /** Explicit negative / decline. Word-boundary matched so "no" never fires inside "uno"/"bueno". */
    private function isNo(string $text): bool
    {
        return (bool) preg_match('/\b(no|nel|nop|nope|todav[ií]a no|a[uú]n no|aun no|por ahora no|'
            .'ahorita no|mejor no|otro plan|otra opci[oó]n|cambiar)\b/u', $text);
    }

    /** The client wants a real person / doesn't want a bot → hand off to a human. */
    private function wantsHuman(string $text): bool
    {
        $text = ' '.trim($text).' ';

        return (bool) preg_match('/(asesor real|un asesor|una asesora|atenci[oó]n humana|'
            .'(hablar|hablo|comunicar|comunicarme|pasar|pasas?|p[aá]same|contactar|conectar|con[eé]ctame) con (un|una|alg[uú]ien|el|la)?\s*(humano|persona|asesor|asesora|agente|ejecutiv|alguien real|alguien|due[ñn]o|encargad)|'
            .'quiero (un|una|hablar con|que me atienda un|que me atienda una)?\s*(humano|persona|asesor|asesora|agente|alguien real)|'
            .'prefiero (un|una|hablar con|que me atienda)?\s*(humano|persona|asesor|asesora|agente|alguien)|'
            .'no quiero (hablar con)?\s*(un|una)?\s*(bot|ia|robot|inteligencia artificial|asistente)|'
            .'hablar con (un|una)?\s*(ia|bot|robot|inteligencia artificial)\s*no|'
            .'no me (interes|gust)\w*\s*(hablar con\s*(un|una)?\s*)?(ia|bot|robot|asistente|inteligencia artificial))/u', $text);
    }

    // ---- utilities ------------------------------------------------------

    /** Persist a step change. */
    private function setStep(BotContact $contact, string $step): void
    {
        $contact->step = $step;
        $contact->save();
    }

    /** Format a price stored in cents as a Spanish-friendly monthly amount. */
    private function formatPrice(int $cents): string
    {
        return '$'.number_format($cents / 100, 0, '.', ',').' MXN/mes';
    }

    /** Every outbound reply goes through the gateway. */
    private function reply(string $to, string $message): void
    {
        $this->gateway->send($to, $message);
    }
}
