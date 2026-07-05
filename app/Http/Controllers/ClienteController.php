<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Pedido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClienteController extends Controller
{
    public function index(Request $request): Response
    {
        $clientes = Cliente::query()
            ->when($request->string('buscar')->toString(), function ($q, $buscar) {
                $q->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('telefono', 'like', "%{$buscar}%");
                });
            })
            ->latest('id')
            ->get();

        // Pedido<->Cliente is matched by phone number (no FK), so tally counts in one query.
        $conteos = Pedido::whereIn('telefono', $clientes->pluck('telefono'))
            ->selectRaw('telefono, count(*) as total')
            ->groupBy('telefono')
            ->pluck('total', 'telefono');

        $clientes->each(fn (Cliente $cliente) => $cliente->pedidos_count = $conteos[$cliente->telefono] ?? 0);

        return Inertia::render('Clientes/Index', [
            'clientes' => $clientes,
            'filtros' => ['buscar' => $request->string('buscar')->toString()],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Clientes/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Cliente::create($this->validated($request));

        return redirect()->route('clientes.index')->with('success', 'Miembro registrado correctamente.');
    }

    public function edit(Cliente $cliente): Response
    {
        return Inertia::render('Clientes/Edit', ['cliente' => $cliente]);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($this->validated($request, $cliente->id));

        return redirect()->route('clientes.index')->with('success', 'Miembro actualizado correctamente.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Miembro eliminado.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'nombre' => ['nullable', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:255', 'unique:clientes,telefono'.($ignoreId ? ",{$ignoreId}" : '')],
        ]);
    }
}
