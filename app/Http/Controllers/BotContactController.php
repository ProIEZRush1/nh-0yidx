<?php

namespace App\Http\Controllers;

use App\Models\BotContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BotContactController extends Controller
{
    public function index(Request $request): Response
    {
        $contactos = BotContact::query()
            ->withCount('pedidos')
            ->when($request->string('buscar')->toString(), function ($q, $buscar) {
                $q->where(function ($q) use ($buscar) {
                    $q->where('name', 'like', "%{$buscar}%")
                        ->orWhere('phone', 'like', "%{$buscar}%");
                });
            })
            ->latest('id')
            ->get();

        return Inertia::render('Contactos/Index', [
            'contactos' => $contactos,
            'filtros' => ['buscar' => $request->string('buscar')->toString()],
        ]);
    }

    public function edit(BotContact $contacto): Response
    {
        return Inertia::render('Contactos/Edit', [
            'contacto' => $contacto->load(['pedidos.plan']),
        ]);
    }

    public function update(Request $request, BotContact $contacto): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'step' => ['required', 'string', 'in:new,choosing,confirming,done,human'],
        ]);

        $contacto->update($data);

        return redirect()->route('contactos.index')->with('success', 'Conversación actualizada correctamente.');
    }

    public function destroy(BotContact $contacto): RedirectResponse
    {
        $contacto->delete();

        return redirect()->route('contactos.index')->with('success', 'Conversación eliminada.');
    }
}
