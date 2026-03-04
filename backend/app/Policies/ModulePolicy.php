<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;

class ModulePolicy
{
    /**
     * Determine whether the user can view any modules.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view modules
    }

    /**
     * Determine whether the user can view the module.
     */
    public function view(User $user, Module $module): bool
    {
        return true; // All users can view modules
    }

    /**
     * Determine whether the user can create modules.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_PM]);
    }

    /**
     * Determine whether the user can update the module.
     */
    public function update(User $user, Module $module): bool
    {
        // Admin and PM can always update
        if ($user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_PM])) {
            return $module->isEditable();
        }
        
        // Engineers can update modules
        if ($user->isEngineer()) {
            return $module->isEditable();
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the module.
     */
    public function delete(User $user, Module $module): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can validate the module.
     */
    public function validate(User $user, Module $module): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_PM]) && $module->isValidatable();
    }

    /**
     * Determine whether the user can change status of the module.
     */
    public function changeStatus(User $user, Module $module): bool
    {
        return $user->hasAnyRole([User::ROLE_ADMIN, User::ROLE_PM]);
    }
}

