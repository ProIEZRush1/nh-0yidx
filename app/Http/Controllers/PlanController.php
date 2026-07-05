<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(Request $request): Response
    {
        $planes = Plan::query()
            ->when($request->string('buscar')->toString(), fn ($q, $buscar) => $q->where('nombre', 'like', "%{$buscar}%"))
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        return Inertia::render('Planes/Index', [
            'planes' => $planes,
            'filtros' => ['buscar' => $request->string('buscar')->toString()],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Planes/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Plan::create($data);

        return redirect()->route('planes.index')->with('success', 'Membresía creada correctamente.');
    }

    public function edit(Plan $plan): Response
    {
        return Inertia::render('Planes/Edit', ['plan' => $plan]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $data = $this->validated($request);

        $plan->update($data);

        return redirect()->route('planes.index')->with('success', 'Membresía actualizada correctamente.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()->route('planes.index')->with('success', 'Membresía eliminada.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'precio' => ['required', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string'],
            'activo' => ['boolean'],
            'orden' => ['integer', 'min:0'],
        ]);

        $data['precio'] = (int) round($data['precio'] * 100);
        $data['activo'] = $data['activo'] ?? true;
        $data['orden'] = $data['orden'] ?? 0;

        return $data;
    }
}
