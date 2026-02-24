<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Fine;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Schedule;
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

        // 1. Clean Database
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        Department::truncate();
        Employee::truncate();
        Schedule::truncate();
        Attendance::truncate();
        LeaveRequest::truncate();
        Holiday::truncate();
        Fine::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Database cleaned. Starting seed.');

        // 2. Create Departments
        $departmentsList = [
            'Human Resources',
            'IT Development',
            'Finance',
            'Operations',
            'Marketing',
            'Security',
            'Office Boy (Cleaning Service)',
            'Warehouse',
        ];

        $deptObjs = [];
        foreach ($departmentsList as $deptName) {
            $deptObjs[$deptName] = Department::create(['name' => $deptName]);
        }

        // 3. Create Holidays
        $holidays = [
            ['name' => 'Tahun Baru', 'date' => '2026-01-01'],
            ['name' => 'Hari Buruh', 'date' => '2026-05-01'],
            ['name' => 'Hari Kemerdekaan RI', 'date' => '2026-08-17'],
        ];
        foreach ($holidays as $h) {
            Holiday::create($h);
        }

        // 4. Helper to Create Employees
        $createEmployee = function ($name, $email, $role, $deptId = null) use ($faker) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);

            // Format dummy family (JSON)
            $familyData = [
                [
                    'name' => $faker->name(),
                    'relation' => $faker->randomElement(['Suami', 'Istri', 'Ayah', 'Ibu', 'Anak']),
                    'phone_number' => $faker->phoneNumber(),
                    'emergency_contact' => true,
                ],
                [
                    'name' => $faker->name(),
                    'relation' => $faker->randomElement(['Saudara Kandung', 'Anak']),
                    'phone_number' => $faker->phoneNumber(),
                    'emergency_contact' => false,
                ],
            ];

            return Employee::create([
                'user_id' => $user->id,
                'department_id' => $deptId,
                'role' => $role,
                'gender' => $faker->randomElement(['PRIA', 'WANITA']),
                'remaining_leave_days' => 12,
                'ktp_photo_path' => 'uploads/documents/dummy_ktp.jpg',
                'kk_photo_path' => 'uploads/documents/dummy_kk.jpg',
                'npwp_number' => $faker->numerify('##.###.###.#-###.###'),
                'pas_photo_path' => 'uploads/documents/dummy_pasfoto.jpg',
                'phone_number' => $faker->phoneNumber(),
                'address' => $faker->address(),
                'home_latitude' => $faker->latitude(-11, 6),
                'home_longitude' => $faker->longitude(95, 141),
                'bank_account_number' => $faker->bankAccountNumber(),
                'family_data' => json_encode($familyData),
            ]);
        };

        // 5. Create Users & Employees
        $admin = $createEmployee('Super Admin', 'admin@company.com', 'ADMIN', null);
        $hrd = $createEmployee($faker->name(), 'hr@company.com', 'HRD', $deptObjs['Human Resources']->id);

        $managers = [];
        foreach ($departmentsList as $deptName) {
            $emailSlug = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $deptName));
            $managers[] = $createEmployee(
                $faker->name(),
                "manager.{$emailSlug}@company.com",
                'MANAGER',
                $deptObjs[$deptName]->id
            );
        }

        $employees = [];
        // Create 50 random employees
        for ($i = 1; $i <= 50; $i++) {
            $firstName = $faker->firstName();
            $randomDept = $deptObjs[$faker->randomElement($departmentsList)];

            $employees[] = $createEmployee(
                $faker->name(),
                strtolower($firstName)."{$i}@company.com",
                'EMPLOYEE',
                $randomDept->id
            );
        }

        $allEmployees = array_merge([$admin, $hrd], $managers, $employees);

        // 6. Create RECURRING Schedules (The New Logic)
        $daysOfWeek = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY']; // Weekend off by default

        foreach ($allEmployees as $emp) {
            // Determine Shift Type based on Department
            $isSecurity = $emp->department && $emp->department->name === 'Security';

            if ($isSecurity) {
                // Security: Overnight Shift (22:00 - 04:00)
                // They work Mon-Sun usually, but let's give them Mon-Fri for simplicity or randomize
                $workDays = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'];
                $startTime = '22:00:00';
                $endTime = '04:00:00';
            } else {
                // Office: Normal Shift (08:00 - 17:00)
                $workDays = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY'];
                $startTime = '08:00:00';
                $endTime = '17:00:00';
            }

            foreach ($workDays as $day) {
                Schedule::create([
                    'employee_id' => $emp->id,
                    'day_of_week' => $day,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ]);
            }
        }

        // 7. Generate Attendances based on Date Range
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now(); // Up to today

        $allEmployeesWithSchedules = Employee::with('schedules')->get();

        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dayName = strtoupper($current->format('l'));

            foreach ($allEmployeesWithSchedules as $emp) {
                $schedule = $emp->schedules->firstWhere('day_of_week', $dayName);

                if (! $schedule) {
                    continue;
                }

                // Randomize Attendance
                $rand = rand(1, 100);
                $status = 'PRESENT';
                $notes = null;

                // Parse start/end times
                $schedStart = Carbon::parse($current->format('Y-m-d').' '.$schedule->start_time);
                $schedEnd = Carbon::parse($current->format('Y-m-d').' '.$schedule->end_time);

                // Handle Overnight Logic (if End Time < Start Time, it ends the next day)
                if ($schedEnd->lt($schedStart)) {
                    $schedEnd->addDay();
                }

                // Default: On Time
                // Clock in: -15 mins to +5 mins
                $clockIn = $schedStart->copy()->subMinutes(rand(0, 15))->addMinutes(rand(0, 5));
                // Clock out: +0 mins to +30 mins
                $clockOut = $schedEnd->copy()->addMinutes(rand(0, 30));

                if ($rand > 85) {
                    // Late
                    $status = 'LATE';
                    $clockIn = $schedStart->copy()->addMinutes(rand(15, 120)); // 15 mins to 2 hours late
                } elseif ($rand > 95) {
                    // Absent
                    $status = 'ABSENT';
                    $clockIn = null;
                    $clockOut = null;
                }

                // Create the record
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

        // 8. Fines (unchanged logic)
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

        // 9. Leave Requests (unchanged logic)
        $hrdId = $hrd->id;
        foreach ($employees as $emp) {
            $manager = collect($managers)->firstWhere('department_id', $emp->department_id);
            $managerId = $manager ? $manager->id : null;

            for ($k = 0; $k < rand(0, 2); $k++) {
                $startLeave = Carbon::now()->subMonths(rand(1, 6))->subDays(rand(1, 20));
                $daysTaken = rand(1, 3);
                $endLeave = $startLeave->copy()->addDays($daysTaken - 1);

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
                }

                LeaveRequest::create([
                    'employee_id' => $emp->id,
                    'type' => $faker->randomElement(['ANNUAL', 'SICK']),
                    'reason' => $faker->sentence(6),
                    'start_date' => $startLeave->format('Y-m-d'),
                    'end_date' => $endLeave->format('Y-m-d'),
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
