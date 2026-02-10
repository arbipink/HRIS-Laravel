<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Fine;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        Department::truncate();
        Employee::truncate();
        Shift::truncate();
        Schedule::truncate();
        Attendance::truncate();
        LeaveRequest::truncate();
        Holiday::truncate();
        Fine::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Database cleaned. Starting seed...');

        $departments = [
            'Human Resources',
            'IT Development',
            'Finance',
            'Operations',
            'Marketing',
        ];

        $deptIds = [];
        foreach ($departments as $dept) {
            $d = Department::create(['name' => $dept]);
            $deptIds[] = $d->id;
        }

        $shifts = [
            ['name' => 'Morning Shift', 'start_time' => '08:00:00', 'end_time' => '17:00:00'],
            ['name' => 'Night Shift', 'start_time' => '20:00:00', 'end_time' => '05:00:00'],
        ];

        $shiftIds = [];
        foreach ($shifts as $shift) {
            $s = Shift::create($shift);
            $shiftIds[] = $s->id;
        }

        $holidays = [
            ['name' => 'New Year', 'date' => '2026-01-01'],
            ['name' => 'Labor Day', 'date' => '2026-05-01'],
            ['name' => 'Independence Day', 'date' => '2026-08-17'],
        ];
        foreach ($holidays as $h) {
            Holiday::create($h);
        }

        $createEmployee = function ($name, $email, $role, $deptId = null) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            return Employee::create([
                'user_id' => $user->id,
                'department_id' => $deptId,
                'name' => $name,
                'role' => $role,
                'gender' => fake()->randomElement(['PRIA', 'WANITA']),
                'remaining_leave_days' => 12,
            ]);
        };

        $admin = $createEmployee('Super Admin', 'admin@company.com', 'ADMIN', null);

        $hrd = $createEmployee('Jane HR', 'hr@company.com', 'HRD', $deptIds[0]);

        $managerIT = $createEmployee('John Manager', 'manager@company.com', 'MANAGER', $deptIds[1]);

        $employees = [];
        for ($i = 1; $i <= 15; $i++) {
            $employees[] = $createEmployee(
                fake()->name(),
                "employee{$i}@company.com",
                'EMPLOYEE',
                fake()->randomElement($deptIds)
            );
        }

        $allEmployees = array_merge([$admin, $hrd, $managerIT], $employees);

        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now()->addDays(7);

        $this->command->info('Generating Roster and Attendance...');

        foreach ($allEmployees as $emp) {
            $current = $startDate->copy();

            while ($current <= $endDate) {
                if ($current->isWeekend()) {
                    $current->addDay();

                    continue;
                }

                $shiftId = fake()->randomElement($shiftIds);
                $schedule = Schedule::create([
                    'employee_id' => $emp->id,
                    'shift_id' => $shiftId,
                    'date' => $current->format('Y-m-d'),
                ]);

                if ($current <= Carbon::now()) {
                    $rand = rand(1, 100);
                    $status = 'PRESENT';
                    $clockIn = $current->copy()->setTime(7, rand(30, 59));
                    $clockOut = $current->copy()->setTime(17, rand(0, 30));
                    $notes = null;

                    if ($rand > 90) {
                        $status = 'LATE';
                        $clockIn = $current->copy()->setTime(8, rand(15, 59));
                    } elseif ($rand > 95) {
                        $status = 'ABSENT';
                        $clockIn = null;
                        $clockOut = null;
                    } elseif ($rand > 98) {
                        $status = 'SICK';
                        $clockIn = null;
                        $clockOut = null;
                        $notes = 'Doctor note attached';
                    }

                    Attendance::create([
                        'employee_id' => $emp->id,
                        'schedule_id' => $schedule->id,
                        'date' => $current->format('Y-m-d'),
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'status' => $status,
                        'notes' => $notes,
                    ]);
                }

                $current->addDay();
            }
        }

        $this->command->info('Generating Leave Requests...');

        $leaveTypes = ['ANNUAL', 'SICK', 'MATERNITY', 'MARRIAGE'];

        foreach ($allEmployees as $emp) {
            $pastStart = Carbon::now()->subDays(rand(10, 60));
            $pastEnd = $pastStart->copy()->addDays(2);

            $pastStatus = fake()->randomElement(['APPROVED', 'REJECTED']);

            LeaveRequest::create([
                'employee_id' => $emp->id,
                'type' => fake()->randomElement($leaveTypes),
                'reason' => 'Family matter',
                'start_date' => $pastStart,
                'end_date' => $pastEnd,
                'status' => $pastStatus,
                'manager_status' => $pastStatus,
                'hrd_status' => $pastStatus,
                'manager_id' => $managerIT->id,
                'hrd_id' => $hrd->id,
            ]);

            if (rand(0, 1)) {
                $futureStart = Carbon::now()->addDays(rand(5, 20));
                $futureEnd = $futureStart->copy()->addDays(rand(1, 3));

                $futureStatus = fake()->randomElement(['PENDING', 'APPROVED', 'REJECTED']);

                $mgrStatus = $futureStatus === 'PENDING' ? fake()->randomElement(['PENDING', 'APPROVED']) : $futureStatus;
                $hrStatus = $futureStatus === 'PENDING' ? 'PENDING' : $futureStatus;

                LeaveRequest::create([
                    'employee_id' => $emp->id,
                    'type' => 'ANNUAL',
                    'reason' => 'Holiday trip',
                    'start_date' => $futureStart,
                    'end_date' => $futureEnd,
                    'status' => $futureStatus,
                    'manager_status' => $mgrStatus,
                    'hrd_status' => $hrStatus,
                    'manager_id' => $managerIT->id,
                    'hrd_id' => $hrd->id,
                ]);
            }
        }

        foreach ($allEmployees as $emp) {
            if (rand(0, 10) > 8) {
                Fine::create([
                    'employee_id' => $emp->id,
                    'date' => Carbon::now()->subDays(rand(1, 20)),
                    'amount' => 50000,
                    'reason' => 'Late more than 30 minutes',
                ]);
            }
        }

        $this->command->info('Seeding completed successfully!');
    }
}
