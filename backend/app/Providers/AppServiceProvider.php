<?php

namespace App\Providers;

use App\Models\Artifact;
use App\Models\Module;
use App\Models\Project;
use App\Models\User;
use App\Policies\ArtifactPolicy;
use App\Policies\ModulePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Artifact::class, ArtifactPolicy::class);
        Gate::policy(Module::class, ModulePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
