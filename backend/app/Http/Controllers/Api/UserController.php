<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * List all users (admin only)
     */
    public function index(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('🔍 UserController@index - Request received', [
            'user_id' => $request->user()?->id,
            'user_role' => $request->user()?->role,
            'params' => $request->all()
        ]);

        // Check authorization
        if (Gate::denies('viewAny', User::class)) {
            \Illuminate\Support\Facades\Log::warning('❌ UserController@index - Authorization denied', [
                'user_id' => $request->user()?->id
            ]);
            return response()->json(['message' => 'Unauthorized - Admin role required to view users'], 403);
        }

        $query = User::query();

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->paginate($request->get('per_page', 15));

        \Illuminate\Support\Facades\Log::debug('✅ UserController@index - Response', [
            'count' => $users->count(),
            'total' => $users->total()
        ]);

        return response()->json($users);
    }

    /**
     * Show a single user
     */
    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    /**
     * Create a new user (admin only)
     */
    public function store(Request $request): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('📥 UserController@store - Request received', [
            'user_id' => $request->user()?->id,
            'data' => $request->except(['password', 'password_confirmation'])
        ]);

        Gate::authorize('create', User::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(User::ROLES)],
        ]);

        $validated['password'] = bcrypt($validated['password']);

        $user = User::create($validated);

        \Illuminate\Support\Facades\Log::debug('✅ UserController@store - User created', [
            'user_id' => $user->id
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    /**
     * Update a user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('📥 UserController@update - Request received', [
            'target_user_id' => $user->id,
            'requesting_user_id' => $request->user()?->id,
            'data' => $request->all()
        ]);

        Gate::authorize('update', $user);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8|confirmed',
            'role' => ['sometimes', Rule::in(User::ROLES)],
        ]);

        // Only admin can change roles
        if (!$request->user()->isAdmin() && isset($validated['role'])) {
            unset($validated['role']);
        }

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Update current user's profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!password_verify($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => bcrypt($validated['password']),
        ]);

        // Revoke all tokens except current
        $currentToken = $user->currentAccessToken();
        $user->tokens()->where('id', '!=', $currentToken->id)->delete();

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Delete a user (admin only)
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        \Illuminate\Support\Facades\Log::debug('📥 UserController@destroy - Request received', [
            'target_user_id' => $user->id,
            'requesting_user_id' => $request->user()?->id
        ]);

        Gate::authorize('delete', $user);

        // Prevent deleting yourself
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot delete your own account'
            ], 422);
        }

        $user->delete();

        \Illuminate\Support\Facades\Log::debug('✅ UserController@destroy - User deleted', [
            'user_id' => $user->id
        ]);

        return response()->json(['message' => 'User deleted successfully']);
    }
}

