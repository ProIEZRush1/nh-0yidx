<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PedidoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'plan_id' => Plan::factory(),
            'cliente' => fake()->name(),
            'telefono' => fake()->unique()->numerify('52155########'),
            'estado' => 'nuevo',
        ];
    }
}
