<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_membership_can_be_created_and_persists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('planes.store'), [
            'nombre' => 'Élite',
            'precio' => 599.50,
            'descripcion' => 'Acceso ilimitado a todo.',
            'activo' => true,
            'orden' => 4,
        ]);

        $response->assertRedirect(route('planes.index'));

        $plan = Plan::where('nombre', 'Élite')->firstOrFail();
        $this->assertSame(59950, $plan->precio);
        $this->assertTrue($plan->activo);

        // Reload from the database to confirm real persistence, not just the in-request model.
        $this->assertDatabaseHas('planes', ['nombre' => 'Élite', 'precio' => 59950]);
    }

    public function test_a_membership_can_be_updated(): void
    {
        $user = User::factory()->create();
        $plan = Plan::factory()->create(['nombre' => 'Básico', 'precio' => 10000]);

        $this->actingAs($user)->put(route('planes.update', $plan), [
            'nombre' => 'Básico',
            'precio' => 150,
            'descripcion' => 'Actualizado',
            'activo' => false,
            'orden' => 1,
        ])->assertRedirect(route('planes.index'));

        $this->assertDatabaseHas('planes', ['id' => $plan->id, 'precio' => 15000, 'activo' => false]);
    }
}
