<?php

namespace Database\Factories;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'type' => LeaveType::ANNUAL,
            'reason' => $this->faker->sentence,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(2),
            'status' => LeaveStatus::PENDING,
            'manager_status' => LeaveStatus::PENDING,
            'hrd_status' => LeaveStatus::PENDING,
        ];
    }
}
