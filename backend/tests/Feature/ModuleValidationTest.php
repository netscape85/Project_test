<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Module validation rule
     * A module cannot be validated if it doesn't have:
     * - objective (not empty)
     * - inputs (at least 1 item)
     * - outputs (at least 1 item)
     * - responsibility (not empty)
     */
    public function test_module_validation_rule(): void
    {
        // Create users
        $admin = User::factory()->create();
        $pm = User::factory()->pm()->create();

        // Create project
        $project = Project::factory()->create([
            'created_by' => $admin->id,
        ]);

        // Test 1: Module without required fields cannot be validated
        $moduleIncomplete = Module::factory()->create([
            'project_id' => $project->id,
            'status' => 'draft',
            'objective' => null,
            'inputs' => null,
            'outputs' => null,
            'responsibility' => null,
        ]);

        // Try to validate (should fail)
        $response = $this->actingAs($pm, 'sanctum')
            ->postJson("/api/modules/{$moduleIncomplete->id}/validate");

        $response->assertStatus(403); // Policy should block it

        // Test 2: Module with all required fields CAN be validated
        $moduleComplete = Module::factory()->create([
            'project_id' => $project->id,
            'status' => 'draft',
            'objective' => 'This is a valid objective for the module',
            'inputs' => ['input1', 'input2'],
            'outputs' => ['output1', 'output2'],
            'responsibility' => 'The operations team is responsible',
        ]);

        // Try to validate (should succeed)
        $response = $this->actingAs($pm, 'sanctum')
            ->postJson("/api/modules/{$moduleComplete->id}/validate");

        $response->assertStatus(200);

        // Verify module is now validated
        $moduleComplete->refresh();
        $this->assertEquals('validated', $moduleComplete->status);

        // Test 3: Module with only some fields cannot be validated
        $modulePartial = Module::factory()->create([
            'project_id' => $project->id,
            'status' => 'draft',
            'objective' => 'Has objective',
            'inputs' => null, // Missing inputs
            'outputs' => ['output1'],
            'responsibility' => null, // Missing responsibility
        ]);

        $response = $this->actingAs($pm, 'sanctum')
            ->postJson("/api/modules/{$modulePartial->id}/validate");

        $response->assertStatus(403);
    }
}

