<?php

namespace App\Policies;

use App\Enums\EmployeeRole;
use App\Models\Holiday;
use App\Models\User;

class HolidayPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Holiday $holiday): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->employee?->role, [EmployeeRole::ADMIN, EmployeeRole::HRD]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Holiday $holiday): bool
    {
        return in_array($user->employee?->role, [EmployeeRole::ADMIN, EmployeeRole::HRD]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Holiday $holiday): bool
    {
        return in_array($user->employee?->role, [EmployeeRole::ADMIN, EmployeeRole::HRD]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Holiday $holiday): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Holiday $holiday): bool
    {
        return false;
    }

    /**
     * Determine whether the user can bulk delete models.
     */
    public function deleteAny(User $user): bool
    {
        return in_array($user->employee?->role, [EmployeeRole::ADMIN, EmployeeRole::HRD]);
    }
}
