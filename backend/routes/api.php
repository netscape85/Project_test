<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArtifactController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectTemplateController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes - Authentication
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
    });

    // Users list for assignment (accessible by admin, pm, engineer)
    Route::get('/users/list', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        // Admin, PM, and Engineer can list users for assignment
        if (!$user->hasAnyRole([\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_PM, \App\Models\User::ROLE_ENGINEER])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return \App\Models\User::select(['id', 'name', 'email', 'role'])->get();
    });

    // Users CRUD routes
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);

    // Project Templates (optional feature)
    Route::apiResource('templates', ProjectTemplateController::class)->except(['update']);
    Route::post('/projects/from-template/{template}', [ProjectTemplateController::class, 'createFromTemplate']);

    // Projects routes
    Route::apiResource('projects', ProjectController::class);
    Route::post('/projects/{project}/archive', [ProjectController::class, 'archive']);
    Route::post('/projects/{id}/restore', [ProjectController::class, 'restore']);
    Route::get('/projects/{project}/export', [ProjectController::class, 'export']);
    
    // Project nested routes (artifacts and modules)
    Route::get('/projects/{project}/artifacts', [ArtifactController::class, 'index']);
    Route::post('/projects/{project}/artifacts', [ArtifactController::class, 'store']);
    Route::get('/projects/{project}/modules', [ModuleController::class, 'index']);
    Route::post('/projects/{project}/modules', [ModuleController::class, 'store']);

    // Artifacts routes
    Route::apiResource('artifacts', ArtifactController::class);
    Route::post('/artifacts/{artifact}/complete', [ArtifactController::class, 'complete']);
    Route::post('/artifacts/{artifact}/change-status', [ArtifactController::class, 'changeStatus']);
    Route::post('/artifacts/{artifact}/assign', [ArtifactController::class, 'assign']);

    // Modules routes
    Route::apiResource('modules', ModuleController::class);
    Route::post('/modules/{module}/validate', [ModuleController::class, 'validate']);
    Route::post('/modules/{module}/change-status', [ModuleController::class, 'changeStatus']);
});

