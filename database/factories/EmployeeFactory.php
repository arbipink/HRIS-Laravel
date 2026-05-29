<?php

namespace Database\Factories;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'department_id' => null,
            'role' => EmployeeRole::EMPLOYEE,
            'gender' => $this->faker->randomElement(['PRIA', 'WANITA']),
            'remaining_leave_days' => 12,
        ];
    }
}
