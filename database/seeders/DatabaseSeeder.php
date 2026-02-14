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
use Faker\Factory as Faker;
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
        $faker = Faker::create('id_ID');

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

        $this->command->info('Database cleaned. Starting seed.');

        $departments = [
            'Human Resources',
            'IT Development',
            'Finance',
            'Operations',
            'Marketing',
            'Security',
            'Office Boy (Cleaning Service)',
            'Warehouse',
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
            ['name' => 'Tahun Baru', 'date' => '2026-01-01'],
            ['name' => 'Hari Buruh', 'date' => '2026-05-01'],
            ['name' => 'Hari Kemerdekaan RI', 'date' => '2026-08-17'],
        ];
        foreach ($holidays as $h) {
            Holiday::create($h);
        }

        $createEmployee = function ($name, $email, $role, $deptId = null) use ($faker) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            return Employee::create([
                'user_id' => $user->id,
                'department_id' => $deptId,
                'role' => $role,
                'gender' => $faker->randomElement(['PRIA', 'WANITA']),
                'remaining_leave_days' => 12,
            ]);
        };

        $admin = $createEmployee('Super Admin', 'admin@company.com', 'ADMIN', null);

        $hrd = $createEmployee($faker->name(), 'hr@company.com', 'HRD', $deptIds[0]);

        $managers = [];
        foreach ($departments as $index => $deptName) {
            $emailSlug = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $deptName));

            $managers[] = $createEmployee(
                $faker->name(),
                "manager.{$emailSlug}@company.com",
                'MANAGER',
                $deptIds[$index]
            );
        }

        $employees = [];
        for ($i = 1; $i <= 50; $i++) {
            $firstName = $faker->firstName();
            $employees[] = $createEmployee(
                $faker->name(),
                strtolower($firstName)."{$i}@company.com",
                'EMPLOYEE',
                $faker->randomElement($deptIds)
            );
        }

        $allEmployees = array_merge([$admin, $hrd], $managers, $employees);

        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now()->addDays(7);

        foreach ($allEmployees as $emp) {
            $current = $startDate->copy();
            while ($current <= $endDate) {
                if ($current->isWeekend()) {
                    $current->addDay();

                    continue;
                }

                $schedule = Schedule::create([
                    'employee_id' => $emp->id,
                    'shift_id' => $faker->randomElement($shiftIds),
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
                    } elseif ($rand > 96) {
                        $status = 'ABSENT';
                        $clockIn = null;
                        $clockOut = null;
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

        foreach ($allEmployees as $emp) {
            if (rand(1, 10) > 8) {
                Fine::create([
                    'employee_id' => $emp->id,
                    'date' => Carbon::now()->subDays(rand(1, 15)),
                    'amount' => 50000,
                    'reason' => 'Terlambat lebih dari 30 menit',
                ]);
            }
        }

        $hrdId = $hrd->id;

        foreach ($employees as $emp) {

            $manager = collect($managers)->firstWhere('department_id', $emp->department_id);
            $managerId = $manager ? $manager->id : null;

            for ($k = 0; $k < rand(1, 3); $k++) {

                $startDate = Carbon::now()->subMonths(rand(1, 6))->subDays(rand(1, 20));
                $daysTaken = rand(1, 3);
                $endDate = $startDate->copy()->addDays($daysTaken - 1);

                $chance = rand(1, 100);

                $finalStatus = 'REJECTED';
                $managerStatus = 'PENDING';
                $hrdStatus = 'PENDING';

                if ($chance <= 70) {
                    $managerStatus = 'APPROVED';
                    $hrdStatus = 'APPROVED';
                    $finalStatus = 'APPROVED';
                } elseif ($chance <= 85) {
                    $managerStatus = 'REJECTED';
                    $hrdStatus = 'PENDING';
                    $finalStatus = 'REJECTED';
                } else {
                    $managerStatus = 'APPROVED';
                    $hrdStatus = 'REJECTED';
                    $finalStatus = 'REJECTED';
                }

                LeaveRequest::create([
                    'employee_id' => $emp->id,
                    'type' => $faker->randomElement(['ANNUAL', 'SICK', 'ANNUAL', 'ANNUAL']),
                    'reason' => $faker->sentence(6),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'attachment_path' => null,

                    'status' => $finalStatus,
                    'manager_status' => $managerStatus,
                    'hrd_status' => $hrdStatus,
                    'manager_id' => $managerId,
                    'hrd_id' => $hrdId,
                ]);
            }
        }

        $this->command->info('Seeding completed successfully!');
    }
}
