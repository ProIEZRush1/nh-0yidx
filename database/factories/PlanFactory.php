<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true),
            'precio' => fake()->numberBetween(50000, 400000),
            'descripcion' => fake()->sentence(),
            'activo' => true,
            'orden' => fake()->numberBetween(0, 10),
        ];
    }
}
