<?php

namespace App\Services;

use App\Models\Artifact;
use App\Models\Module;
use App\Models\Project;
use Illuminate\Support\Facades\Log;

/**
 * Domain Service for Business Rules/Gates
 * 
 * This service enforces all the business rules defined in the TCG Engineering Framework:
 * - Gate 1: Cannot mark domain_breakdown as done if big_picture is not done
 * - Gate 2: Cannot mark module_matrix as done if domain_breakdown is not done
 * - Gate 3: Cannot mark system_architecture as done unless there are at least N validated modules
 * - Gate 4: Cannot move Project status from discovery → execution unless required artifacts are done
 */
class ArtifactGateService
{
    /**
     * Minimum number of validated modules required for system_architecture
     */
    private const MIN_VALIDATED_MODULES = 3;

    /**
     * Required artifacts that must be done before moving to execution
     */
    private const REQUIRED_ARTIFACTS_FOR_EXECUTION = [
        Artifact::TYPE_STRATEGIC_ALIGNMENT,
        Artifact::TYPE_BIG_PICTURE,
        Artifact::TYPE_DOMAIN_BREAKDOWN,
        Artifact::TYPE_MODULE_MATRIX,
    ];

    /**
     * Check if an artifact can be marked as done
     * 
     * @param Artifact $artifact The artifact to check
     * @return array{allowed: bool, reason: string|null}
     */
    public function canMarkAsDone(Artifact $artifact): array
    {
        // Only check gates when trying to mark as done
        if ($artifact->status === Artifact::STATUS_DONE) {
            return ['allowed' => true, 'reason' => null];
        }

        switch ($artifact->type) {
            case Artifact::TYPE_DOMAIN_BREAKDOWN:
                return $this->checkGate1($artifact);

            case Artifact::TYPE_MODULE_MATRIX:
                return $this->checkGate2($artifact);

            case Artifact::TYPE_SYSTEM_ARCHITECTURE:
                return $this->checkGate3($artifact);

            default:
                return ['allowed' => true, 'reason' => null];
        }
    }

    /**
     * Gate 1: Cannot mark domain_breakdown as done if big_picture is not done
     */
    private function checkGate1(Artifact $artifact): array
    {
        $bigPicture = Artifact::where('project_id', $artifact->project_id)
            ->where('type', Artifact::TYPE_BIG_PICTURE)
            ->first();

        if (!$bigPicture || $bigPicture->status !== Artifact::STATUS_DONE) {
            return [
                'allowed' => false,
                'reason' => 'Gate 1: Cannot mark Domain Breakdown as done because Big Picture is not completed yet.'
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Gate 2: Cannot mark module_matrix as done if domain_breakdown is not done
     */
    private function checkGate2(Artifact $artifact): array
    {
        $domainBreakdown = Artifact::where('project_id', $artifact->project_id)
            ->where('type', Artifact::TYPE_DOMAIN_BREAKDOWN)
            ->first();

        if (!$domainBreakdown || $domainBreakdown->status !== Artifact::STATUS_DONE) {
            return [
                'allowed' => false,
                'reason' => 'Gate 2: Cannot mark Module Matrix as done because Domain Breakdown is not completed yet.'
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Gate 3: Cannot mark system_architecture as done unless there are at least N validated modules
     */
    private function checkGate3(Artifact $artifact): array
    {
        $validatedModulesCount = Module::where('project_id', $artifact->project_id)
            ->where('status', 'validated')
            ->count();

        if ($validatedModulesCount < self::MIN_VALIDATED_MODULES) {
            return [
                'allowed' => false,
                'reason' => "Gate 3: Cannot mark System Architecture as done because there are not enough validated modules. Required: " . self::MIN_VALIDATED_MODULES . ", Current: {$validatedModulesCount}"
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Check if a project can change status to execution
     * 
     * Gate 4: Cannot move Project status from discovery → execution unless:
     * - strategic_alignment is done
     * - big_picture is done
     * - domain_breakdown is done
     * - module_matrix is done
     * 
     * @param Project $project The project
     * @param string $newStatus The new status
     * @return array{allowed: bool, reason: string|null, missing_artifacts: array}
     */
    public function canChangeProjectStatus(Project $project, string $newStatus): array
    {
        // Only check when moving to execution
        if ($newStatus !== 'execution') {
            return ['allowed' => true, 'reason' => null, 'missing_artifacts' => []];
        }

        // Only check if current status is discovery
        if ($project->status !== 'discovery') {
            return ['allowed' => true, 'reason' => null, 'missing_artifacts' => []];
        }

        $missingArtifacts = [];

        foreach (self::REQUIRED_ARTIFACTS_FOR_EXECUTION as $artifactType) {
            $artifact = Artifact::where('project_id', $project->id)
                ->where('type', $artifactType)
                ->first();

            if (!$artifact || $artifact->status !== Artifact::STATUS_DONE) {
                $missingArtifacts[] = $this->getArtifactTypeName($artifactType);
            }
        }

        if (!empty($missingArtifacts)) {
            return [
                'allowed' => false,
                'reason' => 'Gate 4: Cannot move to Execution phase because the following artifacts are not completed: ' . implode(', ', $missingArtifacts),
                'missing_artifacts' => $missingArtifacts
            ];
        }

        return ['allowed' => true, 'reason' => null, 'missing_artifacts' => []];
    }

    /**
     * Get blocking reason for an artifact (for UI display)
     * 
     * @param Artifact $artifact
     * @return string|null
     */
    public function getBlockingReason(Artifact $artifact)
    {
        if ($artifact->status === Artifact::STATUS_DONE) {
            return null;
        }

        $result = $this->canMarkAsDone($artifact);
        
        return $result['allowed'] ? null : $result['reason'];
    }

    /**
     * Get all blocking reasons for a project's artifacts
     * 
     * @param Project $project
     * @return array
     */
    public function getProjectBlockingReasons(Project $project): array
    {
        $reasons = [];

        // Check each artifact type
        $artifactTypes = [
            Artifact::TYPE_BIG_PICTURE,
            Artifact::TYPE_DOMAIN_BREAKDOWN,
            Artifact::TYPE_MODULE_MATRIX,
            Artifact::TYPE_SYSTEM_ARCHITECTURE,
        ];

        foreach ($artifactTypes as $type) {
            $artifact = Artifact::where('project_id', $project->id)
                ->where('type', $type)
                ->first();

            if ($artifact && $artifact->status !== Artifact::STATUS_DONE) {
                $blockingReason = $this->getBlockingReason($artifact);
                if ($blockingReason) {
                    $reasons[$type] = $blockingReason;
                }
            }
        }

        return $reasons;
    }

    /**
     * Get human-readable name for artifact type
     */
    private function getArtifactTypeName(string $type): string
    {
        return match ($type) {
            Artifact::TYPE_STRATEGIC_ALIGNMENT => 'Strategic Alignment',
            Artifact::TYPE_BIG_PICTURE => 'Big Picture',
            Artifact::TYPE_DOMAIN_BREAKDOWN => 'Domain Breakdown',
            Artifact::TYPE_MODULE_MATRIX => 'Module Matrix',
            Artifact::TYPE_MODULE_ENGINEERING => 'Module Engineering',
            Artifact::TYPE_SYSTEM_ARCHITECTURE => 'System Architecture',
            Artifact::TYPE_PHASE_SCOPE => 'Phase Scope',
            default => $type,
        };
    }
}

