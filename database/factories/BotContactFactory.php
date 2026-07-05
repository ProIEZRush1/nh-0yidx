<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BotContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'phone' => fake()->unique()->numerify('52155########'),
            'name' => fake()->name(),
            'step' => 'new',
            'data' => null,
        ];
    }
}
