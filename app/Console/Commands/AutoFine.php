<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Fine;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoFine extends Command
{
    protected $signature = 'app:attendance-cleanup';

    protected $description = 'Auto-fine for missing clock-outs and mark absences';

    public function handle()
    {
        $this->checkMissingClockOuts();
        $this->checkAbsences();
    }

    protected function checkMissingClockOuts()
    {
        $openAttendances = Attendance::whereNull('clock_out')
            ->where('date', '>=', now()->subDays(2))
            ->with('schedule')
            ->get();

        $gracePeriodHours = 12;

        foreach ($openAttendances as $attendance) {
            if (! $attendance->schedule) {
                continue;
            }

            $shiftStart = Carbon::parse($attendance->date.' '.$attendance->schedule->start_time);
            $shiftEnd = Carbon::parse($attendance->date.' '.$attendance->schedule->end_time);

            if ($shiftEnd->lessThan($shiftStart)) {
                $shiftEnd->addDay();
            }

            $cutoffTime = $shiftEnd->copy()->addHours($gracePeriodHours);

            if (now()->greaterThan($cutoffTime)) {
                $alreadyFined = Fine::where('attendance_id', $attendance->id)->exists();

                if (! $alreadyFined) {
                    Fine::create([
                        'employee_id' => $attendance->employee_id,
                        'attendance_id' => $attendance->id,
                        'date' => $attendance->date,
                        'amount' => 50000,
                        'reason' => 'Forgot to Clock Out',
                    ]);

                    $attendance->update(['clock_out' => $cutoffTime]);
                    $this->info("Fined (No Clock Out): Employee {$attendance->employee_id}");
                }
            }
        }
    }

    protected function checkAbsences()
    {
        $yesterday = Carbon::yesterday();
        $yesterdayDate = $yesterday->toDateString();
        $yesterdayDayName = strtoupper($yesterday->format('l'));

        $isHoliday = Holiday::where('date', $yesterdayDate)->exists();

        if ($isHoliday) {
            $this->info("Yesterday ($yesterdayDate) was a Holiday. Skipping absence checks.");

            return;
        }

        $employees = Employee::all();

        foreach ($employees as $employee) {

            $schedule = Schedule::where('employee_id', $employee->id)
                ->where('day_of_week', $yesterdayDayName)
                ->first();

            if (! $schedule) {
                continue;
            }

            $attendanceExists = Attendance::where('employee_id', $employee->id)
                ->where('date', $yesterdayDate)
                ->exists();

            if ($attendanceExists) {
                continue;
            }

            $onLeave = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', LeaveStatus::APPROVED)
                ->whereDate('start_date', '<=', $yesterdayDate)
                ->whereDate('end_date', '>=', $yesterdayDate)
                ->exists();

            if ($onLeave) {
                $this->info("Employee {$employee->id} is on Approved Leave. Skipping.");

                Attendance::firstOrCreate([
                    'employee_id' => $employee->id,
                    'date' => $yesterdayDate,
                ], [
                    'status' => AttendanceStatus::LEAVE,
                    'schedule_id' => $schedule->id,
                ]);

                continue;
            }

            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'schedule_id' => $schedule->id,
                'date' => $yesterdayDate,
                'status' => AttendanceStatus::ABSENT,
            ]);

            Fine::create([
                'employee_id' => $employee->id,
                'attendance_id' => $attendance->id,
                'date' => $yesterdayDate,
                'amount' => 100000,
                'reason' => 'Absent without notice',
            ]);

            $this->info("Fined (Absent): Employee {$employee->id}");
        }
    }
}
