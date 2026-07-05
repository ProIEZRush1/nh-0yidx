<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_inscripcion_can_be_created_and_persists(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create();

        $response = $this->actingAs($user)->post(route('pedidos.store'), [
            'cliente' => 'Sofía Ramírez',
            'telefono' => '5215500009999',
            'plan_id' => $plan->id,
            'estado' => 'nuevo',
        ]);

        $response->assertRedirect(route('pedidos.index'));

        $this->assertDatabaseHas('pedidos', [
            'cliente' => 'Sofía Ramírez',
            'telefono' => '5215500009999',
            'estado' => 'nuevo',
        ]);
    }

    public function test_confirming_an_inscripcion_is_blocked_while_the_trial_is_locked(): void
    {
        config(['trial.locked' => true]);

        $user = User::factory()->create();
        $pedido = Pedido::factory()->create(['estado' => 'nuevo']);

        $this->actingAs($user)->put(route('pedidos.update', $pedido), [
            'cliente' => $pedido->cliente,
            'telefono' => $pedido->telefono,
            'plan_id' => $pedido->plan_id,
            'estado' => 'confirmado',
        ])->assertRedirect(route('pedidos.edit', $pedido));

        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'estado' => 'nuevo']);
    }

    public function test_confirming_an_inscripcion_works_once_the_trial_is_unlocked(): void
    {
        config(['trial.locked' => false]);

        $user = User::factory()->create();
        $pedido = Pedido::factory()->create(['estado' => 'nuevo']);

        $this->actingAs($user)->put(route('pedidos.update', $pedido), [
            'cliente' => $pedido->cliente,
            'telefono' => $pedido->telefono,
            'plan_id' => $pedido->plan_id,
            'estado' => 'confirmado',
        ])->assertRedirect(route('pedidos.index'));

        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id, 'estado' => 'confirmado']);
    }
}
