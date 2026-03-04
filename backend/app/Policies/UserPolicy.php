<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_PM]);
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $user, User $targetUser): bool
    {
        // Admin and PM can view any user
        if ($user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_PM])) {
            return true;
        }

        // Users can view their own profile
        return $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $targetUser): bool
    {
        // Admin can update any user
        if ($user->isAdmin()) {
            return true;
        }

        // PM can update users but not admins
        if ($user->isPM() && !$targetUser->isAdmin()) {
            return true;
        }

        // Users can update their own profile (but not role)
        return $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $targetUser): bool
    {
        // Admin can delete any user except themselves
        if ($user->isAdmin() && $user->id !== $targetUser->id) {
            return true;
        }

        // PM can delete engineers and viewers
        if ($user->isPM() && $targetUser->hasAnyRole([User::ROLE_ENGINEER, User::ROLE_VIEWER])) {
            return true;
        }

        return false;
    }
}

