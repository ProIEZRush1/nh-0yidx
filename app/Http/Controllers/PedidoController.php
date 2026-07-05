<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PedidoController extends Controller
{
    public function index(Request $request): Response
    {
        $pedidos = Pedido::query()
            ->with('plan')
            ->when($request->string('buscar')->toString(), function ($q, $buscar) {
                $q->where(function ($q) use ($buscar) {
                    $q->where('cliente', 'like', "%{$buscar}%")
                        ->orWhere('telefono', 'like', "%{$buscar}%");
                });
            })
            ->latest('id')
            ->get();

        return Inertia::render('Pedidos/Index', [
            'pedidos' => $pedidos,
            'filtros' => ['buscar' => $request->string('buscar')->toString()],
            'trialLocked' => trial_locked(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Pedidos/Create', [
            'planes' => Plan::orderBy('orden')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Pedido::create($data);

        if (($data['estado'] ?? null) === 'confirmado') {
            Cliente::updateOrCreate(
                ['telefono' => $data['telefono']],
                ['nombre' => $data['cliente']],
            );
        }

        return redirect()->route('pedidos.index')->with('success', 'Inscripción registrada correctamente.');
    }

    public function edit(Pedido $pedido): Response
    {
        return Inertia::render('Pedidos/Edit', [
            'pedido' => $pedido,
            'planes' => Plan::orderBy('orden')->orderBy('id')->get(),
            'trialLocked' => trial_locked(),
        ]);
    }

    public function update(Request $request, Pedido $pedido): RedirectResponse
    {
        $data = $this->validated($request);

        // Confirming a sign-up is the money-making step: gate it behind the trial lock so a demo
        // deploy can manage everything else but not officially close real sales.
        if (($data['estado'] ?? null) === 'confirmado' && trial_locked()) {
            return redirect()->route('pedidos.edit', $pedido)
                ->with('error', trial_locked_message());
        }

        $pedido->update($data);

        if ($pedido->estado === 'confirmado') {
            Cliente::updateOrCreate(
                ['telefono' => $pedido->telefono],
                ['nombre' => $pedido->cliente],
            );
        }

        return redirect()->route('pedidos.index')->with('success', 'Inscripción actualizada correctamente.');
    }

    public function destroy(Pedido $pedido): RedirectResponse
    {
        $pedido->delete();

        return redirect()->route('pedidos.index')->with('success', 'Inscripción eliminada.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'cliente' => ['required', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:255'],
            'plan_id' => ['nullable', 'exists:planes,id'],
            'estado' => ['required', 'string', 'in:nuevo,pendiente_pago,confirmado,cancelado'],
            'fecha' => ['nullable', 'date'],
            'motivo' => ['nullable', 'string'],
        ]);
    }
}
