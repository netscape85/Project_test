<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'client_name' => fake()->company(),
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the project is in discovery phase.
     */
    public function discovery(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'discovery',
        ]);
    }

    /**
     * Indicate that the project is in execution phase.
     */
    public function execution(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'execution',
        ]);
    }

    /**
     * Indicate that the project is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
        ]);
    }
}

