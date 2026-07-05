<?php

namespace App\Http\Controllers;

use App\Models\BotContact;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Plan;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'planes' => Plan::count(),
                'clientes' => Cliente::count(),
                'pedidosMes' => Pedido::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'pedidosConfirmados' => Pedido::where('estado', 'confirmado')->count(),
                'conversaciones' => BotContact::count(),
                'ingresosMes' => (int) Pedido::query()
                    ->join('planes', 'planes.id', '=', 'pedidos.plan_id')
                    ->where('pedidos.estado', 'confirmado')
                    ->whereMonth('pedidos.created_at', now()->month)
                    ->whereYear('pedidos.created_at', now()->year)
                    ->sum('planes.precio'),
            ],
        ]);
    }
}
