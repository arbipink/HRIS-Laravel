<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Fine;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Handle employee clock-in.
     */
    public function clockIn(Employee $employee): array
    {
        return DB::transaction(function () use ($employee) {
            $now = now();
            $todayDate = $now->toDateString();

            // 1. Holiday Check
            if (Holiday::where('date', $todayDate)->exists()) {
                return ['success' => false, 'message' => 'Today is a Holiday. Clock-in is not required.'];
            }

            // 2. Leave Check
            $onLeave = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', LeaveStatus::APPROVED)
                ->whereDate('start_date', '<=', $todayDate)
                ->whereDate('end_date', '>=', $todayDate)
                ->exists();

            if ($onLeave) {
                return ['success' => false, 'message' => 'You are on approved leave today.'];
            }

            // 3. Schedule Check
            $dayName = strtoupper($now->format('l'));
            $schedule = Schedule::where('employee_id', $employee->id)
                ->where('day_of_week', $dayName)
                ->first();

            if (! $schedule) {
                return ['success' => false, 'message' => 'No schedule found for today.'];
            }

            // 4. Existing Attendance Check
            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $todayDate)
                ->first();

            if ($existingAttendance) {
                return ['success' => false, 'message' => 'You have already clocked in today.'];
            }

            // 5. Validation (e.g., 30 mins before)
            $scheduleStartTime = Carbon::parse($schedule->start_time)->setDateFrom($now);
            $earliestClockIn = $scheduleStartTime->copy()->subMinutes(Fine::GRACE_PERIOD_MINUTES);

            if ($now->lessThan($earliestClockIn)) {
                return [
                    'success' => false,
                    'message' => 'Too early to clock in. Earliest is '.$earliestClockIn->format('H:i'),
                ];
            }

            // 6. Status Determination
            $isLate = $now->greaterThan($scheduleStartTime);
            $status = $isLate ? AttendanceStatus::LATE : AttendanceStatus::PRESENT;

            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'schedule_id' => $schedule->id,
                'date' => $todayDate,
                'clock_in' => $now,
                'status' => $status,
            ]);

            if ($isLate) {
                Fine::create([
                    'employee_id' => $employee->id,
                    'date' => $todayDate,
                    'amount' => Fine::LATE_FINE,
                    'reason' => 'Late Arrival: Expected '.$scheduleStartTime->format('H:i').', arrived '.$now->format('H:i'),
                ]);

                return [
                    'success' => true,
                    'message' => 'Clocked in (Late). Fine of '.number_format(Fine::LATE_FINE).' recorded.',
                    'late' => true,
                ];
            }

            return ['success' => true, 'message' => 'Clocked in successfully.'];
        });
    }

    /**
     * Handle employee clock-out.
     */
    public function clockOut(Employee $employee): array
    {
        return DB::transaction(function () use ($employee) {
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereNull('clock_out')
                ->latest('clock_in')
                ->first();

            if (! $attendance) {
                return ['success' => false, 'message' => 'No active attendance record found.'];
            }

            $now = now();
            $schedule = $attendance->schedule;
            $message = 'Clocked out successfully.';

            if ($schedule) {
                $scheduleEndTime = Carbon::parse($schedule->end_time);
                $clockInDate = Carbon::parse($attendance->date);

                // Handle shift spanning past midnight
                $scheduleStartTime = Carbon::parse($schedule->start_time);
                if ($scheduleEndTime->lessThan($scheduleStartTime)) {
                    $scheduleEndTime = $scheduleEndTime->setDateFrom($clockInDate)->addDay();
                } else {
                    $scheduleEndTime = $scheduleEndTime->setDateFrom($clockInDate);
                }

                // Non-blocking Early Leave
                if ($now->lessThan($scheduleEndTime)) {
                    $attendance->status = AttendanceStatus::EARLY_LEAVE;
                    $message = 'Clocked out. Marked as Early Leave.';
                }
            }

            $attendance->update([
                'clock_out' => $now,
            ]);

            return ['success' => true, 'message' => $message];
        });
    }

    /**
     * Run all automated attendance tasks.
     */
    public function processAutoFines(): void
    {
        $this->checkMissingClockOuts();
        $this->checkAbsences(Carbon::yesterday());
    }

    /**
     * Find attendances without clock-outs and apply fines.
     */
    protected function checkMissingClockOuts(): void
    {
        Attendance::whereNull('clock_out')
            ->where('date', '>=', now()->subDays(2))
            ->with('schedule')
            ->chunkById(100, function ($attendances) {
                foreach ($attendances as $attendance) {
                    if (! $attendance->schedule) {
                        continue;
                    }

                    $shiftStart = Carbon::parse($attendance->date.' '.$attendance->schedule->start_time);
                    $shiftEnd = Carbon::parse($attendance->date.' '.$attendance->schedule->end_time);

                    if ($shiftEnd->lessThan($shiftStart)) {
                        $shiftEnd->addDay();
                    }

                    $cutoffTime = $shiftEnd->copy()->addHours(Fine::AUTO_CLOCK_OUT_GRACE_HOURS);

                    if (now()->greaterThan($cutoffTime)) {
                        DB::transaction(function () use ($attendance, $cutoffTime) {
                            Fine::create([
                                'employee_id' => $attendance->employee_id,
                                'date' => $attendance->date,
                                'amount' => Fine::NO_CLOCK_OUT_FINE,
                                'reason' => 'Forgot to Clock Out',
                            ]);

                            $attendance->update(['clock_out' => $cutoffTime]);
                        });
                    }
                }
            });
    }

    /**
     * Mark employees as absent and apply fines if they didn't show up.
     */
    public function checkAbsences(Carbon $date): void
    {
        $dateStr = $date->toDateString();
        $dayName = strtoupper($date->format('l'));

        if (Holiday::where('date', $dateStr)->exists()) {
            return;
        }

        Employee::query()
            ->with([
                'schedules' => fn ($q) => $q->where('day_of_week', $dayName),
                'attendances' => fn ($q) => $q->where('date', $dateStr),
                'leaveRequests' => fn ($q) => $q->where('status', LeaveStatus::APPROVED)
                    ->whereDate('start_date', '<=', $dateStr)
                    ->whereDate('end_date', '>=', $dateStr),
            ])
            ->chunkById(100, function ($employees) use ($dateStr) {
                foreach ($employees as $employee) {
                    $schedule = $employee->schedules->first();

                    if (! $schedule) {
                        continue;
                    }

                    if ($employee->attendances->isNotEmpty()) {
                        continue;
                    }

                    $onLeave = $employee->leaveRequests->isNotEmpty();

                    DB::transaction(function () use ($employee, $schedule, $dateStr, $onLeave) {
                        if ($onLeave) {
                            Attendance::firstOrCreate([
                                'employee_id' => $employee->id,
                                'date' => $dateStr,
                            ], [
                                'status' => AttendanceStatus::LEAVE,
                                'schedule_id' => $schedule->id,
                            ]);

                            return;
                        }

                        Attendance::create([
                            'employee_id' => $employee->id,
                            'schedule_id' => $schedule->id,
                            'date' => $dateStr,
                            'status' => AttendanceStatus::ABSENT,
                        ]);

                        Fine::create([
                            'employee_id' => $employee->id,
                            'date' => $dateStr,
                            'amount' => Fine::ABSENT_FINE,
                            'reason' => 'Absent without notice',
                        ]);
                    });
                }
            });
    }
}
