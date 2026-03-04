<?php

namespace App\Policies;

use App\Models\Artifact;
use App\Models\User;

class ArtifactPolicy
{
    /**
     * Determine whether the user can view any artifacts.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view artifacts
    }

    /**
     * Determine whether the user can view the artifact.
     */
    public function view(User $user, Artifact $artifact): bool
    {
        return true; // All users can view artifacts
    }

    /**
     * Determine whether the user can create artifacts.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_PM]);
    }

    /**
     * Determine whether the user can update the artifact.
     */
    public function update(User $user, Artifact $artifact): bool
    {
        // Admin and PM can always update
        if ($user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_PM])) {
            return $artifact->isEditable();
        }
        
        // Engineers can update if they own it
        if ($user->isEngineer() && $artifact->owner_user_id === $user->id) {
            return $artifact->isEditable();
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the artifact.
     */
    public function delete(User $user, Artifact $artifact): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can assign the artifact.
     */
    public function assign(User $user, Artifact $artifact): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_PM]);
    }

    /**
     * Determine whether the user can complete the artifact.
     */
    public function complete(User $user, Artifact $artifact): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_PM]) && $artifact->isCompletable();
    }

    /**
     * Determine whether the user can change status of the artifact.
     */
    public function changeStatus(User $user, Artifact $artifact): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_PM]);
    }
}

