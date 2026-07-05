<?php

use App\Http\Controllers\BotContactController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Admin/bot panel: the root goes straight to the panel (login if needed), never a generic page.
Route::get('/', fn () => redirect()->route('dashboard'));

// Lightweight health probe the deploy pipeline hits to verify the LIVE app + database are up,
// migrations ran and the admin was seeded (users >= 1). Public on purpose.
Route::get('/health', function () {
    try {
        return response()->json(['ok' => true, 'users' => \App\Models\User::count()]);
    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => 'db'], 503);
    }
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/conectar', [WhatsAppController::class, 'conectar'])->name('conectar');

    // English inflection would singularize "planes" to "plane" (as in aircraft); force "plan" so
    // route-model-binding actually matches the {plan} parameter used by PlanController.
    Route::resource('planes', PlanController::class)->parameters(['planes' => 'plan'])->except('show');
    Route::resource('pedidos', PedidoController::class)->except('show');
    Route::resource('clientes', ClienteController::class)->except('show');
    Route::resource('contactos', BotContactController::class)->except(['show', 'create', 'store']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
