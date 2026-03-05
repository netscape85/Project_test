<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     * This is for the users management page, not for listing users to assign.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $user, User $targetUser): bool
    {
        // Admin can view any user
        if ($user->isAdmin()) {
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

        // Users can update their own profile (but not role)
        return $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $targetUser): bool
    {
        // Only admin can delete users (except themselves)
        return $user->isAdmin() && $user->id !== $targetUser->id;
    }
}

