<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'domain' => fake()->word(),
            'name' => fake()->words(3, true),
            'status' => 'draft',
            'objective' => null,
            'inputs' => null,
            'data_structure' => null,
            'logic_rules' => null,
            'outputs' => null,
            'responsibility' => null,
            'failure_scenarios' => null,
            'audit_trail_requirements' => null,
            'dependencies' => null,
            'version_note' => null,
        ];
    }

    /**
     * Indicate that the module is validated.
     */
    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'validated',
            'objective' => 'Test objective',
            'inputs' => ['input1'],
            'outputs' => ['output1'],
            'responsibility' => 'Test responsibility',
        ]);
    }

    /**
     * Indicate that the module is ready for build.
     */
    public function readyForBuild(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ready_for_build',
        ]);
    }

    /**
     * Indicate that the module has all required fields for validation.
     */
    public function withRequiredFields(): static
    {
        return $this->state(fn (array $attributes) => [
            'objective' => 'This is a valid objective for the module',
            'inputs' => ['input1', 'input2'],
            'outputs' => ['output1', 'output2'],
            'responsibility' => 'The operations team is responsible',
        ]);
    }
}

