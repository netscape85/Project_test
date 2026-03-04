<?php

namespace Tests\Feature;

use App\Models\Artifact;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Gate4ProjectExecutionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Project cannot move discovery → execution
     * unless all required artifacts are done:
     * - strategic_alignment
     * - big_picture
     * - domain_breakdown
     * - module_matrix
     */
    public function test_project_cannot_move_discovery_to_execution_without_required_artifacts(): void
    {
        // Create users
        $admin = User::factory()->create();
        $pm = User::factory()->pm()->create();

        // Create project in discovery phase
        $project = Project::factory()->create([
            'status' => 'discovery',
            'created_by' => $admin->id,
        ]);

        // Test 1: Try to move to execution without any artifacts done (should fail)
        $response = $this->actingAs($pm, 'sanctum')
            ->putJson("/api/projects/{$project->id}", [
                'status' => 'execution',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot move to Execution phase',
                'gate_blocked' => true,
            ]);

        // Verify project status is still discovery
        $project->refresh();
        $this->assertEquals('discovery', $project->status);

        // Test 2: Mark some artifacts as done, but not all required ones (should fail)
        Artifact::factory()->create([
            'project_id' => $project->id,
            'type' => Artifact::TYPE_STRATEGIC_ALIGNMENT,
            'status' => Artifact::STATUS_DONE,
        ]);

        Artifact::factory()->create([
            'project_id' => $project->id,
            'type' => Artifact::TYPE_BIG_PICTURE,
            'status' => Artifact::STATUS_DONE,
        ]);

        // domain_breakdown NOT done
        Artifact::factory()->create([
            'project_id' => $project->id,
            'type' => Artifact::TYPE_DOMAIN_BREAKDOWN,
            'status' => Artifact::STATUS_IN_PROGRESS,
        ]);

        // module_matrix NOT done
        Artifact::factory()->create([
            'project_id' => $project->id,
            'type' => Artifact::TYPE_MODULE_MATRIX,
            'status' => Artifact::STATUS_NOT_STARTED,
        ]);

        $response = $this->actingAs($pm, 'sanctum')
            ->putJson("/api/projects/{$project->id}", [
                'status' => 'execution',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'gate_blocked' => true,
            ]);

        // Test 3: Mark ALL required artifacts as done (should succeed)
        // Update domain_breakdown
        $domainBreakdown = Artifact::where('project_id', $project->id)
            ->where('type', Artifact::TYPE_DOMAIN_BREAKDOWN)
            ->first();
        $domainBreakdown->update(['status' => Artifact::STATUS_DONE]);

        // Update module_matrix
        $moduleMatrix = Artifact::where('project_id', $project->id)
            ->where('type', Artifact::TYPE_MODULE_MATRIX)
            ->first();
        $moduleMatrix->update(['status' => Artifact::STATUS_DONE]);

        $response = $this->actingAs($pm, 'sanctum')
            ->putJson("/api/projects/{$project->id}", [
                'status' => 'execution',
            ]);

        $response->assertStatus(200);

        // Verify project is now in execution
        $project->refresh();
        $this->assertEquals('execution', $project->status);
    }
}

