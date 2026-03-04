<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\AuditEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ModuleController extends Controller
{
    /**
     * List all modules (with filters)
     */
    public function index(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('🔍 ModuleController@index - Request received', [
            'user_id' => $request->user()?->id,
            'params' => $request->all()
        ]);

        $query = Module::query();

        // Filter by project
        if ($request->has('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by domain
        if ($request->has('domain')) {
            $query->where('domain', $request->domain);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $modules = $query->paginate($request->get('per_page', 15));

        \Illuminate\Support\Facades\Log::debug('✅ ModuleController@index - Response', [
            'count' => $modules->count(),
            'total' => $modules->total()
        ]);

        return response()->json($modules);
    }

    /**
     * Create a new module
     */
    public function store(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('📥 ModuleController@store - Request received', [
            'user_id' => $request->user()?->id,
            'data' => $request->all()
        ]);

        Gate::authorize('create', Module::class);

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'domain' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'status' => 'sometimes|in:draft,validated,ready_for_build',
            'objective' => 'sometimes|string',
            'inputs' => 'sometimes|array',
            'data_structure' => 'sometimes|string',
            'logic_rules' => 'sometimes|string',
            'outputs' => 'sometimes|array',
            'responsibility' => 'sometimes|string',
            'failure_scenarios' => 'sometimes|string',
            'audit_trail_requirements' => 'sometimes|string',
            'dependencies' => 'sometimes|array',
            'version_note' => 'sometimes|string',
        ]);

        $validated['status'] = $validated['status'] ?? 'draft';

        $module = Module::create($validated);

        // Log audit event
        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_MODULE,
            $module->id,
            AuditEvent::ACTION_CREATED,
            null,
            $module->toArray()
        );

        \Illuminate\Support\Facades\Log::debug('✅ ModuleController@store - Module created', [
            'module_id' => $module->id
        ]);

        return response()->json([
            'message' => 'Module created successfully',
            'module' => $module
        ], 201);
    }

    /**
     * Show a single module
     */
    public function show(Module $module): JsonResponse
    {
        Gate::authorize('view', $module);

        return response()->json($module);
    }

    /**
     * Update a module
     */
    public function update(Request $request, Module $module): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('📥 ModuleController@update - Request received', [
            'module_id' => $module->id,
            'user_id' => $request->user()?->id,
            'data' => $request->all()
        ]);

        Gate::authorize('update', $module);

        $validated = $request->validate([
            'domain' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:draft,validated,ready_for_build',
            'objective' => 'sometimes|string',
            'inputs' => 'sometimes|array',
            'data_structure' => 'sometimes|string',
            'logic_rules' => 'sometimes|string',
            'outputs' => 'sometimes|array',
            'responsibility' => 'sometimes|string',
            'failure_scenarios' => 'sometimes|string',
            'audit_trail_requirements' => 'sometimes|string',
            'dependencies' => 'sometimes|array',
            'version_note' => 'sometimes|string',
        ]);

        $before = $module->toArray();
        $module->update($validated);
        $after = $module->fresh()->toArray();

        // Log audit event
        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_MODULE,
            $module->id,
            $request->has('status') ? AuditEvent::ACTION_STATUS_CHANGED : AuditEvent::ACTION_UPDATED,
            $before,
            $after
        );

        \Illuminate\Support\Facades\Log::debug('✅ ModuleController@update - Module updated', [
            'module_id' => $module->id
        ]);

        return response()->json([
            'message' => 'Module updated successfully',
            'module' => $module->fresh()
        ]);
    }

    /**
     * Validate a module
     */
    public function validate(Request $request, Module $module): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('📥 ModuleController@validate - Request received', [
            'module_id' => $module->id,
            'user_id' => $request->user()?->id
        ]);

        Gate::authorize('validate', $module);

        $before = $module->toArray();
        $module->update(['status' => 'validated']);
        $after = $module->fresh()->toArray();

        // Log audit event
        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_MODULE,
            $module->id,
            AuditEvent::ACTION_VALIDATED,
            $before,
            $after
        );

        \Illuminate\Support\Facades\Log::debug('✅ ModuleController@validate - Module validated', [
            'module_id' => $module->id
        ]);

        return response()->json([
            'message' => 'Module validated successfully',
            'module' => $module->fresh()
        ]);
    }

    /**
     * Change module status
     */
    public function changeStatus(Request $request, Module $module): JsonResponse
    {
        Gate::authorize('changeStatus', $module);

        $validated = $request->validate([
            'status' => 'required|in:draft,validated,ready_for_build',
        ]);

        $before = $module->toArray();
        $module->update(['status' => $validated['status']]);
        $after = $module->fresh()->toArray();

        // Log audit event
        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_MODULE,
            $module->id,
            AuditEvent::ACTION_STATUS_CHANGED,
            $before,
            $after
        );

        return response()->json([
            'message' => 'Module status updated successfully',
            'module' => $module->fresh()
        ]);
    }

    /**
     * Delete a module
     */
    public function destroy(Request $request, Module $module): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('📥 ModuleController@destroy - Request received', [
            'module_id' => $module->id,
            'user_id' => $request->user()?->id
        ]);

        Gate::authorize('delete', $module);

        $before = $module->toArray();
        $module->delete();

        AuditEvent::logChange(
            $request->user()->id,
            AuditEvent::ENTITY_MODULE,
            $module->id,
            AuditEvent::ACTION_DELETED,
            $before,
            null
        );

        \Illuminate\Support\Facades\Log::debug('✅ ModuleController@destroy - Module deleted', [
            'module_id' => $module->id
        ]);

        return response()->json(['message' => 'Module deleted successfully']);
    }
}

