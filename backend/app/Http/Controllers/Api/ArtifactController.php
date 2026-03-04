<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Artifact;
use App\Models\AuditEvent;
use App\Services\ArtifactGateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use function app;

class ArtifactController extends Controller
{
    protected ArtifactGateService $gateService;

    public function __construct(ArtifactGateService $gateService)
    {
        $this->gateService = $gateService;
    }

    /**
     * List all artifacts (with filters)
     */
    public function index(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('🔍 ArtifactController@index - Request received', [
            'user_id' => $request->user()?->id,
            'params' => $request->all()
        ]);

        $query = Artifact::query();

        // Filter by project
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by owner
        if ($request->has('owner_user_id')) {
            $query->where('owner_user_id', $request->owner_user_id);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $artifacts = $query->paginate($request->get('per_page', 15));

        // Add blocking reasons to each artifact
        $gateService = app(ArtifactGateService::class);
        $artifacts->getCollection()->transform(function ($artifact) use ($gateService) {
            $artifact->blocking_reason = $gateService->getBlockingReason($artifact);
            return $artifact;
        });

        \Illuminate\Support\Facades\Log::debug('✅ ArtifactController@index - Response', [
            'count' => $artifacts->count(),
            'total' => $artifacts->total()
        ]);

        return response()->json($artifacts);
    }

    /**
     * Create a new artifact
     */
    public function store(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('📥 ArtifactController@store - Request received', [
            'user_id' => $request->user()?->id,
            'data' => $request->all()
        ]);

        Gate::authorize('create', Artifact::class);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'type' => 'required|in:' . implode(',', Artifact::TYPES),
            'status' => 'sometimes|in:not_started,in_progress,blocked,done',
            'owner_user_id' => 'sometimes|nullable|exists:users,id',
            'content_json' => 'sometimes|array',
        ]);

        $validated['status'] = $validated['status'] ?? 'not_started';

        $artifact = Artifact::create($validated);

        // Log audit event
        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_ARTIFACT,
            $artifact->id,
            AuditEvent::ACTION_CREATED,
            null,
            $artifact->toArray()
        );

        \Illuminate\Support\Facades\Log::debug('✅ ArtifactController@store - Artifact created', [
            'artifact_id' => $artifact->id
        ]);

        return response()->json([
            'message' => 'Artifact created successfully',
            'artifact' => $artifact
        ], 201);
    }

    /**
     * Show a single artifact
     */
    public function show(Artifact $artifact): JsonResponse
    {
        Gate::authorize('view', $artifact);

        return response()->json($artifact);
    }

    /**
     * Update an artifact
     */
    public function update(Request $request, Artifact $artifact): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('📥 ArtifactController@update - Request received', [
            'artifact_id' => $artifact->id,
            'user_id' => $request->user()?->id,
            'data' => $request->all()
        ]);

        Gate::authorize('update', $artifact);

        $validated = $request->validate([
            'type' => 'sometimes|in:' . implode(',', Artifact::TYPES),
            'status' => 'sometimes|in:not_started,in_progress,blocked,done',
            'owner_user_id' => 'sometimes|nullable|exists:users,id',
            'content_json' => 'sometimes|array',
        ]);

        // Check gate rules if status is being changed to done
        if (isset($validated['status']) && $validated['status'] === Artifact::STATUS_DONE) {
            $gateResult = $this->gateService->canMarkAsDone($artifact);
            if (!$gateResult['allowed']) {
                \Illuminate\Support\Facades\Log::warning('❌ ArtifactController@update - Gate blocked', [
                    'artifact_id' => $artifact->id,
                    'reason' => $gateResult['reason']
                ]);
                return response()->json([
                    'message' => 'Cannot complete this artifact',
                    'error' => $gateResult['reason'],
                    'gate_blocked' => true
                ], 422);
            }
        }

        $before = $artifact->toArray();
        $artifact->update($validated);
        $after = $artifact->fresh()->toArray();

        // Log audit event
        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_ARTIFACT,
            $artifact->id,
            $request->has('status') ? AuditEvent::ACTION_STATUS_CHANGED : AuditEvent::ACTION_UPDATED,
            $before,
            $after
        );

        \Illuminate\Support\Facades\Log::debug('✅ ArtifactController@update - Artifact updated', [
            'artifact_id' => $artifact->id
        ]);

        return response()->json([
            'message' => 'Artifact updated successfully',
            'artifact' => $artifact->fresh()
        ]);
    }

    /**
     * Mark artifact as completed
     */
    public function complete(Request $request, Artifact $artifact): JsonResponse
    {
        Gate::authorize('complete', $artifact);

        // Check gate rules
        $gateResult = $this->gateService->canMarkAsDone($artifact);
        if (!$gateResult['allowed']) {
            return response()->json([
                'message' => 'Cannot complete this artifact',
                'error' => $gateResult['reason'],
                'gate_blocked' => true
            ], 422);
        }

        $before = $artifact->toArray();
        $artifact->markAsCompleted();
        $after = $artifact->fresh()->toArray();

        // Log audit event
        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_ARTIFACT,
            $artifact->id,
            AuditEvent::ACTION_COMPLETED,
            $before,
            $after
        );

        return response()->json([
            'message' => 'Artifact marked as completed',
            'artifact' => $artifact->fresh()
        ]);
    }

    /**
     * Change artifact status
     */
    public function changeStatus(Request $request, Artifact $artifact): JsonResponse
    {
        Gate::authorize('changeStatus', $artifact);

        $validated = $request->validate([
            'status' => 'required|in:not_started,in_progress,blocked,done',
        ]);

        // Check gate rules if status is being changed to done
        if ($validated['status'] === Artifact::STATUS_DONE) {
            $gateResult = $this->gateService->canMarkAsDone($artifact);
            if (!$gateResult['allowed']) {
                return response()->json([
                    'message' => 'Cannot complete this artifact',
                    'error' => $gateResult['reason'],
                    'gate_blocked' => true
                ], 422);
            }
        }

        $before = $artifact->toArray();
        $artifact->update(['status' => $validated['status']]);
        $after = $artifact->fresh()->toArray();

        // Log audit event
        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_ARTIFACT,
            $artifact->id,
            AuditEvent::ACTION_STATUS_CHANGED,
            $before,
            $after
        );

        return response()->json([
            'message' => 'Artifact status updated successfully',
            'artifact' => $artifact->fresh()
        ]);
    }

    /**
     * Assign artifact to user
     */
    public function assign(Request $request, Artifact $artifact): JsonResponse
    {
        Gate::authorize('assign', $artifact);

        $validated = $request->validate([
            'owner_user_id' => 'required|nullable|exists:users,id',
        ]);

        $before = $artifact->toArray();
        $artifact->update($validated);
        $after = $artifact->fresh()->toArray();

        // Log audit event
        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_ARTIFACT,
            $artifact->id,
            AuditEvent::ACTION_UPDATED,
            $before,
            $after
        );

        return response()->json([
            'message' => 'Artifact assigned successfully',
            'artifact' => $artifact->fresh()
        ]);
    }

    /**
     * Delete an artifact
     */
    public function destroy(Request $request, Artifact $artifact): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('📥 ArtifactController@destroy - Request received', [
            'artifact_id' => $artifact->id,
            'user_id' => $request->user()?->id
        ]);

        Gate::authorize('delete', $artifact);

        $before = $artifact->toArray();
        $artifact->delete();

        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_ARTIFACT,
            $artifact->id,
            AuditEvent::ACTION_DELETED,
            $before,
            null
        );

        \Illuminate\Support\Facades\Log::debug('✅ ArtifactController@destroy - Artifact deleted', [
            'artifact_id' => $artifact->id
        ]);

        return response()->json(['message' => 'Artifact deleted successfully']);
    }
}

