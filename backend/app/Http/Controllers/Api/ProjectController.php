<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\AuditEvent;
use App\Services\ArtifactGateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    protected ArtifactGateService $gateService;

    public function __construct(ArtifactGateService $gateService)
    {
        $this->gateService = $gateService;
    }

    /**
     * List all projects (with filters)
     */
    public function index(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('🔍 ProjectController@index - Request received', [
            'user_id' => $request->user()?->id,
            'params' => $request->all()
        ]);

        $query = Project::with('creator:id,name,email');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by client
        if ($request->has('client_name')) {
            $query->where('client_name', 'like', '%' . $request->client_name . '%');
        }

        // Search by name
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('client_name', 'like', '%' . $request->search . '%');
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $projects = $query->paginate($request->get('per_page', 15));

        \Illuminate\Support\Facades\Log::debug('✅ ProjectController@index - Response', [
            'count' => $projects->count(),
            'total' => $projects->total()
        ]);

        return response()->json($projects);
    }

    /**
     * Create a new project
     */
    public function store(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('📥 ProjectController@store - Request received', [
            'user_id' => $request->user()?->id,
            'data' => $request->all()
        ]);

        Gate::authorize('create', Project::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'client_name' => 'required|string|max:255',
            'status' => 'sometimes|in:draft,discovery,execution,delivered',
        ]);

        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['created_by'] = $request->user()->id;

        $project = Project::create($validated);

        // Log audit event
        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_PROJECT,
            $project->id,
            AuditEvent::ACTION_CREATED,
            null,
            $project->toArray()
        );

        \Illuminate\Support\Facades\Log::debug('✅ ProjectController@store - Project created', [
            'project_id' => $project->id
        ]);

        return response()->json([
            'message' => 'Project created successfully',
            'project' => $project
        ], 201);
    }

    /**
     * Show a single project
     */
    public function show(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        // Load audit events for this project
        $auditEvents = AuditEvent::where('entity_type', AuditEvent::ENTITY_PROJECT)
            ->where('entity_id', $project->id)
            ->with('actor:id,name,email')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $projectData = $project->toArray();
        $projectData['auditEvents'] = $auditEvents;

        return response()->json($projectData);
    }

    /**
     * Update a project
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('📥 ProjectController@update - Request received', [
            'project_id' => $project->id,
            'user_id' => $request->user()?->id,
            'data' => $request->all()
        ]);

        Gate::authorize('update', $project);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'client_name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:draft,discovery,execution,delivered',
        ]);

        // Check Gate 4: Cannot move Project from discovery → execution unless required artifacts are done
        if (isset($validated['status']) && $validated['status'] === 'execution') {
            $gateResult = $this->gateService->canChangeProjectStatus($project, 'execution');
            if (!$gateResult['allowed']) {
                \Illuminate\Support\Facades\Log::warning('❌ ProjectController@update - Gate blocked', [
                    'project_id' => $project->id,
                    'reason' => $gateResult['reason']
                ]);
                return response()->json([
                    'message' => 'Cannot move to Execution phase',
                    'error' => $gateResult['reason'],
                    'missing_artifacts' => $gateResult['missing_artifacts'],
                    'gate_blocked' => true
                ], 422);
            }
        }

        $before = $project->toArray();
        $project->update($validated);
        $after = $project->fresh()->toArray();

        // Log audit event
        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_PROJECT,
            $project->id,
            $request->has('status') ? AuditEvent::ACTION_STATUS_CHANGED : AuditEvent::ACTION_UPDATED,
            $before,
            $after
        );

        \Illuminate\Support\Facades\Log::debug('✅ ProjectController@update - Project updated', [
            'project_id' => $project->id
        ]);

        return response()->json([
            'message' => 'Project updated successfully',
            'project' => $project->fresh()
        ]);
    }

    /**
     * Archive a project
     */
    public function archive(Request $request, Project $project): JsonResponse
    {
        Gate::authorize('archive', $project);

        $project->update(['status' => 'delivered']);

        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_PROJECT,
            $project->id,
            AuditEvent::ACTION_STATUS_CHANGED,
            null,
            ['status' => 'delivered']
        );

        return response()->json([
            'message' => 'Project archived successfully',
            'project' => $project->fresh()
        ]);
    }

    /**
     * Delete (soft delete) a project
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('📥 ProjectController@destroy - Request received', [
            'project_id' => $project->id,
            'user_id' => $request->user()?->id
        ]);

        Gate::authorize('delete', $project);

        $before = $project->toArray();
        $project->delete();

        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_PROJECT,
            $project->id,
            AuditEvent::ACTION_DELETED,
            $before,
            null
        );

        return response()->json(['message' => 'Project deleted successfully']);
    }

    /**
     * Export project to JSON
     */
    public function export(Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $data = [
            'project' => $project->only(['name', 'client_name', 'status', 'created_at']),
            'artifacts' => $project->artifacts()->get()->map(function ($artifact) {
                return [
                    'type' => $artifact->type,
                    'status' => $artifact->status,
                    'content_json' => $artifact->content_json,
                    'owner' => $artifact->owner ? $artifact->owner->only(['name', 'email']) : null,
                ];
            }),
            'modules' => $project->modules()->get()->map(function ($module) {
                return [
                    'name' => $module->name,
                    'domain' => $module->domain,
                    'status' => $module->status,
                    'objective' => $module->objective,
                    'inputs' => $module->inputs,
                    'outputs' => $module->outputs,
                    'data_structure' => $module->data_structure,
                    'logic_rules' => $module->logic_rules,
                    'responsibility' => $module->responsibility,
                    'failure_scenarios' => $module->failure_scenarios,
                    'audit_trail_requirements' => $module->audit_trail_requirements,
                    'dependencies' => $module->dependencies,
                    'version_note' => $module->version_note,
                ];
            }),
            'exported_at' => now()->toIso8601String(),
        ];

        return response()->json($data);
    }

    /**
     * Restore a deleted project
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        $project = Project::withTrashed()->findOrFail($id);
        
        Gate::authorize('restore', $project);

        $project->restore();

        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_PROJECT,
            $project->id,
            AuditEvent::ACTION_RESTORED,
            null,
            $project->toArray()
        );

        return response()->json([
            'message' => 'Project restored successfully',
            'project' => $project->fresh()
        ]);
    }
}