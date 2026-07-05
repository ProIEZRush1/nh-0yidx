<?php

namespace Tests\Feature;

use App\Models\BotContact;
use App\Models\Pedido;
use App\Models\Plan;
use App\Services\BotEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BotEngineFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_sale_flow_confirms_when_the_trial_is_unlocked(): void
    {
        config(['trial.locked' => false]);
        Http::fake(['*/send' => Http::response(['ok' => true], 200)]);

        $plan = Plan::factory()->create(['nombre' => 'Básico', 'activo' => true]);
        $engine = app(BotEngine::class);
        $phone = '5215500002222';

        $engine->handle($phone, 'Carla', 'hola');
        $this->assertSame('choosing', BotContact::where('phone', $phone)->value('step'));

        $engine->handle($phone, 'Carla', '1');
        $this->assertSame('confirming', BotContact::where('phone', $phone)->value('step'));
        $this->assertDatabaseHas('pedidos', ['telefono' => $phone, 'plan_id' => $plan->id, 'estado' => 'nuevo']);

        $engine->handle($phone, 'Carla', 'sí');

        $this->assertSame('done', BotContact::where('phone', $phone)->value('step'));
        $this->assertDatabaseHas('pedidos', ['telefono' => $phone, 'estado' => 'confirmado']);
        $this->assertDatabaseHas('clientes', ['telefono' => $phone, 'nombre' => 'Carla']);
    }

    public function test_sale_confirmation_is_gated_while_the_trial_is_locked(): void
    {
        config(['trial.locked' => true]);
        Http::fake(['*/send' => Http::response(['ok' => true], 200)]);

        Plan::factory()->create(['nombre' => 'Premium', 'activo' => true]);
        $engine = app(BotEngine::class);
        $phone = '5215500003333';

        $engine->handle($phone, 'Diego', 'hola');
        $engine->handle($phone, 'Diego', '1');
        $engine->handle($phone, 'Diego', 'sí');

        // The lead is still captured, but the sale is not officially confirmed.
        $this->assertDatabaseHas('pedidos', ['telefono' => $phone, 'estado' => 'pendiente_pago']);
        $this->assertDatabaseMissing('pedidos', ['telefono' => $phone, 'estado' => 'confirmado']);

        Http::assertSent(fn ($request) => str_contains($request['text'] ?? '', 'anticipo'));
    }

    public function test_asking_for_a_human_hands_off_and_silences_the_bot(): void
    {
        Http::fake(['*/send' => Http::response(['ok' => true], 200)]);

        $engine = app(BotEngine::class);
        $phone = '5215500004444';

        $engine->handle($phone, 'Marta', 'quiero hablar con un asesor');

        $this->assertSame('human', BotContact::where('phone', $phone)->value('step'));

        Http::fake(['*/send' => Http::response(['ok' => true], 200)]);
        $engine->handle($phone, 'Marta', 'hola de nuevo');

        Http::assertNothingSent();
    }
}
