<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function createEmployee($email = 'test@example.com')
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);

        return Employee::create([
            'user_id' => $user->id,
            'role' => 'EMPLOYEE',
            'gender' => 'PRIA',
            'remaining_leave_days' => 12,
        ]);
    }

    public function test_check_missing_clock_outs_updates_records(): void
    {
        $employee = $this->createEmployee();
        $schedule = Schedule::create([
            'employee_id' => $employee->id,
            'day_of_week' => 'MONDAY',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        $yesterday = now()->subDay();
        // Force yesterday to be a workday if it was a weekend
        $yesterdayDayName = strtoupper($yesterday->format('l'));
        $schedule->update(['day_of_week' => $yesterdayDayName]);

        $attendance = Attendance::create([
            'employee_id' => $employee->id,
            'schedule_id' => $schedule->id,
            'date' => $yesterday->toDateString(),
            'clock_in' => $yesterday->copy()->setTime(9, 0),
            'clock_out' => null,
            'status' => AttendanceStatus::PRESENT,
        ]);

        $service = new AttendanceService;
        $service->processAutoFines();

        $attendance->refresh();
        $this->assertNotNull($attendance->clock_out);
        $this->assertDatabaseHas('fines', [
            'employee_id' => $employee->id,
            'reason' => __('service.attendance.reasons.forgot_clock_out'),
        ]);
    }

    public function test_check_absences_marks_missing_employees(): void
    {
        $date = Carbon::yesterday();
        $dayName = strtoupper($date->format('l'));

        $employee = $this->createEmployee('absent@example.com');
        Schedule::create([
            'employee_id' => $employee->id,
            'day_of_week' => $dayName,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        $service = new AttendanceService;
        $service->checkAbsences($date);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'date' => $date->toDateString(),
            'status' => AttendanceStatus::ABSENT,
        ]);

        $this->assertDatabaseHas('fines', [
            'employee_id' => $employee->id,
            'date' => $date->toDateString(),
            'reason' => __('service.attendance.reasons.absent'),
        ]);
    }
}
