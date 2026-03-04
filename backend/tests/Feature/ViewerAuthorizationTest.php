<?php

namespace Tests\Feature;

use App\Models\Artifact;
use App\Models\Module;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Viewer role cannot edit modules or artifacts
     * According to requirements:
     * - viewer: read-only
     */
    public function test_viewer_cannot_edit_modules_or_artifacts(): void
    {
        // Create users
        $admin = User::factory()->create();
        $viewer = User::factory()->create(['role' => 'viewer']);

        // Create project
        $project = Project::factory()->create([
            'created_by' => $admin->id,
        ]);

        // Create artifact
        $artifact = Artifact::factory()->create([
            'project_id' => $project->id,
            'status' => 'in_progress',
        ]);

        // Create module
        $module = Module::factory()->create([
            'project_id' => $project->id,
            'status' => 'draft',
        ]);

        // Test 1: Viewer cannot update artifact
        $response = $this->actingAs($viewer, 'sanctum')
            ->putJson("/api/artifacts/{$artifact->id}", [
                'status' => 'done',
            ]);

        $response->assertStatus(403);

        // Test 2: Viewer cannot create artifact
        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/artifacts", [
                'project_id' => $project->id,
                'type' => 'strategic_alignment',
                'status' => 'not_started',
            ]);

        $response->assertStatus(403);

        // Test 3: Viewer cannot delete artifact
        $response = $this->actingAs($viewer, 'sanctum')
            ->deleteJson("/api/artifacts/{$artifact->id}");

        $response->assertStatus(403);

        // Test 4: Viewer cannot update module
        $response = $this->actingAs($viewer, 'sanctum')
            ->putJson("/api/modules/{$module->id}", [
                'name' => 'Updated name',
            ]);

        $response->assertStatus(403);

        // Test 5: Viewer cannot create module
        $response = $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/modules", [
                'project_id' => $project->id,
                'domain' => 'test',
                'name' => 'Test module',
            ]);

        $response->assertStatus(403);

        // Test 6: Viewer cannot delete module
        $response = $this->actingAs($viewer, 'sanctum')
            ->deleteJson("/api/modules/{$module->id}");

        $response->assertStatus(403);

        // Test 7: Viewer CAN view (read-only) - this should work
        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/artifacts/{$artifact->id}");

        $response->assertStatus(200);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/modules/{$module->id}");

        $response->assertStatus(200);

        $response = $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/projects");

        $response->assertStatus(200);
    }
}

