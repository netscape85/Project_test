<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Artifact>
 */
class ArtifactFactory extends Factory
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
            'type' => 'strategic_alignment',
            'status' => 'not_started',
            'owner_user_id' => null,
            'content_json' => [],
            'completed_at' => null,
        ];
    }

    /**
     * Indicate that the artifact is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    /**
     * Indicate that the artifact is done.
     */
    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'done',
            'completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the artifact is big_picture type.
     */
    public function bigPicture(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'big_picture',
        ]);
    }

    /**
     * Indicate that the artifact is domain_breakdown type.
     */
    public function domainBreakdown(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'domain_breakdown',
        ]);
    }

    /**
     * Indicate that the artifact is module_matrix type.
     */
    public function moduleMatrix(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'module_matrix',
        ]);
    }

    /**
     * Indicate that the artifact is system_architecture type.
     */
    public function systemArchitecture(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'system_architecture',
        ]);
    }
}

