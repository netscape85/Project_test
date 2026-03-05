<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artifact;
use App\Models\Project;
use App\Models\ProjectTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectTemplateController extends Controller
{
    public function index(): JsonResponse
    {
        $templates = ProjectTemplate::all();
        return response()->json($templates);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'artifact_types' => 'required|array',
            'artifact_types.*' => 'in:' . implode(',', array_keys(Artifact::TYPES)),
        ]);

        $template = ProjectTemplate::create($validated);

        return response()->json($template, 201);
    }

    public function show(ProjectTemplate $template): JsonResponse
    {
        return response()->json($template);
    }

    public function destroy(ProjectTemplate $template): JsonResponse
    {
        $template->delete();
        return response()->json(['message' => 'Template deleted successfully']);
    }

    public function createFromTemplate(Request $request, ProjectTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_name' => 'required|string|max:255',
        ]);

        $project = Project::create([
            'name' => $validated['name'],
            'client_name' => $validated['client_name'],
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        foreach ($template->artifact_types as $type) {
            Artifact::create([
                'project_id' => $project->id,
                'type' => $type,
                'status' => Artifact::STATUS_NOT_STARTED,
                'content_json' => [],
            ]);
        }

        return response()->json([
            'message' => 'Project created from template',
            'project' => $project->load('artifacts'),
        ], 201);
    }
}
