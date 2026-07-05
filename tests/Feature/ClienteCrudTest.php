<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_can_be_created_and_persists(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('clientes.store'), [
            'nombre' => 'Roberto Díaz',
            'telefono' => '5215500001234',
        ]);

        $response->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('clientes', [
            'nombre' => 'Roberto Díaz',
            'telefono' => '5215500001234',
        ]);
    }
}
