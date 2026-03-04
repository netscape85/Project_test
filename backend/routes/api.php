<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArtifactController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\ProjectController;
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

    // Users routes (for listing users to assign)
    Route::get('/users/list', function () {
        return \App\Models\User::select(['id', 'name', 'email', 'role'])->get();
    });

    // Users CRUD routes
    Route::apiResource('users', \App\Http\Controllers\Api\UserController::class);

    // Projects routes
    Route::apiResource('projects', ProjectController::class);
    Route::post('/projects/{project}/archive', [ProjectController::class, 'archive']);
    Route::post('/projects/{id}/restore', [ProjectController::class, 'restore']);
    
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

