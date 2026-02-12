<?php

namespace App\Policies;

use App\Enums\EmployeeRole;
use App\Models\Fine;
use App\Models\User;

class FinePolicy
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
    public function view(User $user, Fine $fine): bool
    {
        $userEmployee = $user->employee;

        if (! $userEmployee) {
            return false;
        }

        if (in_array($userEmployee->role, [EmployeeRole::ADMIN, EmployeeRole::HRD])) {
            return true;
        }

        if ($userEmployee->role === EmployeeRole::MANAGER) {
            return $fine->employee && $fine->employee->department_id === $userEmployee->department_id;
        }

        return $fine->employee_id === $userEmployee->id;
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
    public function update(User $user, Fine $fine): bool
    {
        return in_array($user->employee?->role, [EmployeeRole::ADMIN, EmployeeRole::HRD]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Fine $fine): bool
    {
        return in_array($user->employee?->role, [EmployeeRole::ADMIN, EmployeeRole::HRD]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Fine $fine): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Fine $fine): bool
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
