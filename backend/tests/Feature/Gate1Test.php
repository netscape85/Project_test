<?php

namespace Tests\Feature;

use App\Models\Artifact;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Gate1Test extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Gate 1 enforcement
     * domain_breakdown cannot be done if big_picture is not done
     */
    public function test_gate_1_domain_breakdown_requires_big_picture_done(): void
    {
        // Create users
        $admin = User::factory()->create();
        $pm = User::factory()->pm()->create();

        // Create project
        $project = Project::factory()->create([
            'status' => 'discovery',
            'created_by' => $admin->id,
        ]);

        // Create big_picture artifact (NOT done)
        $bigPicture = Artifact::factory()->create([
            'project_id' => $project->id,
            'type' => Artifact::TYPE_BIG_PICTURE,
            'status' => Artifact::STATUS_IN_PROGRESS, // NOT done
        ]);

        // Create domain_breakdown artifact
        $domainBreakdown = Artifact::factory()->create([
            'project_id' => $project->id,
            'type' => Artifact::TYPE_DOMAIN_BREAKDOWN,
            'status' => Artifact::STATUS_IN_PROGRESS,
        ]);

        // Try to mark domain_breakdown as done (should fail - Gate 1)
        $response = $this->actingAs($pm, 'sanctum')
            ->putJson("/api/artifacts/{$domainBreakdown->id}", [
                'status' => Artifact::STATUS_DONE,
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Cannot complete this artifact',
                'gate_blocked' => true,
            ])
            ->assertJsonStructure([
                'error',
            ]);

        // Verify artifact is NOT marked as done
        $domainBreakdown->refresh();
        $this->assertNotEquals(Artifact::STATUS_DONE, $domainBreakdown->status);

        // Now mark big_picture as done
        $this->actingAs($pm, 'sanctum')
            ->putJson("/api/artifacts/{$bigPicture->id}", [
                'status' => Artifact::STATUS_DONE,
            ]);

        // Now try to mark domain_breakdown as done (should succeed)
        $response = $this->actingAs($pm, 'sanctum')
            ->putJson("/api/artifacts/{$domainBreakdown->id}", [
                'status' => Artifact::STATUS_DONE,
            ]);

        $response->assertStatus(200);

        // Verify artifact is marked as done
        $domainBreakdown->refresh();
        $this->assertEquals(Artifact::STATUS_DONE, $domainBreakdown->status);
    }
}

