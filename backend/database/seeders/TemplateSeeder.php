<?php

namespace Database\Seeders;

use App\Models\Artifact;
use App\Models\ProjectTemplate;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        ProjectTemplate::create([
            'name' => 'Full Framework Template',
            'description' => 'Complete TCG Engineering Framework with all 7 artifacts',
            'artifact_types' => [
                Artifact::TYPE_STRATEGIC_ALIGNMENT,
                Artifact::TYPE_BIG_PICTURE,
                Artifact::TYPE_DOMAIN_BREAKDOWN,
                Artifact::TYPE_MODULE_MATRIX,
                Artifact::TYPE_MODULE_ENGINEERING,
                Artifact::TYPE_SYSTEM_ARCHITECTURE,
                Artifact::TYPE_PHASE_SCOPE,
            ],
        ]);

        ProjectTemplate::create([
            'name' => 'Discovery Phase Template',
            'description' => 'Template for discovery phase with essential artifacts',
            'artifact_types' => [
                Artifact::TYPE_STRATEGIC_ALIGNMENT,
                Artifact::TYPE_BIG_PICTURE,
                Artifact::TYPE_DOMAIN_BREAKDOWN,
                Artifact::TYPE_MODULE_MATRIX,
            ],
        ]);

        ProjectTemplate::create([
            'name' => 'Quick Start Template',
            'description' => 'Minimal template to get started quickly',
            'artifact_types' => [
                Artifact::TYPE_STRATEGIC_ALIGNMENT,
                Artifact::TYPE_BIG_PICTURE,
            ],
        ]);
    }
}
