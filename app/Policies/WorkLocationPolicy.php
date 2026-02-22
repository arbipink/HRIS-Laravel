<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkLocation;
use App\Enums\EmployeeRole;
use Illuminate\Auth\Access\Response;

class WorkLocationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->employee?->role === EmployeeRole::ADMIN;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WorkLocation $workLocation): bool
    {
        return $user->employee?->role === EmployeeRole::ADMIN;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->employee?->role === EmployeeRole::ADMIN;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WorkLocation $workLocation): bool
    {
        return $user->employee?->role === EmployeeRole::ADMIN;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WorkLocation $workLocation): bool
    {
        return $user->employee?->role === EmployeeRole::ADMIN;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WorkLocation $workLocation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WorkLocation $workLocation): bool
    {
        return false;
    }
}
