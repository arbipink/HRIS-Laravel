<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
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
            $settings = AttendanceSetting::getSettings();
            $now = now();
            $todayDate = $now->toDateString();

            // 1. Holiday Check
            if (Holiday::where('date', $todayDate)->exists()) {
                return ['success' => false, 'message' => __('service.attendance.messages.holiday')];
            }

            // 2. Leave Check
            $onLeave = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', LeaveStatus::APPROVED)
                ->whereDate('start_date', '<=', $todayDate)
                ->whereDate('end_date', '>=', $todayDate)
                ->exists();

            if ($onLeave) {
                return ['success' => false, 'message' => __('service.attendance.messages.on_leave')];
            }

            // 3. Schedule Check
            $dayName = strtoupper($now->format('l'));
            $schedule = Schedule::where('employee_id', $employee->id)
                ->where('day_of_week', $dayName)
                ->first();

            if (! $schedule) {
                return ['success' => false, 'message' => __('service.attendance.messages.no_schedule')];
            }

            // 4. Existing Attendance Check
            $existingAttendance = Attendance::where('employee_id', $employee->id)
                ->where('date', $todayDate)
                ->first();

            if ($existingAttendance) {
                return ['success' => false, 'message' => __('service.attendance.messages.already_clocked_in')];
            }

            // 5. Validation (e.g., 30 mins before)
            $scheduleStartTime = Carbon::parse($schedule->start_time)->setDateFrom($now);
            $earliestClockIn = $scheduleStartTime->copy()->subMinutes($settings->grace_period_minutes);

            if ($now->lessThan($earliestClockIn)) {
                return [
                    'success' => false,
                    'message' => __('service.attendance.messages.too_early', ['time' => $earliestClockIn->format('H:i')]),
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
                    'amount' => $settings->late_fine_amount,
                    'reason' => __('service.attendance.reasons.late_arrival', [
                        'expected' => $scheduleStartTime->format('H:i'),
                        'arrived' => $now->format('H:i'),
                    ]),
                ]);

                return [
                    'success' => true,
                    'message' => __('service.attendance.messages.clock_in_late', ['amount' => number_format($settings->late_fine_amount)]),
                    'late' => true,
                ];
            }

            return ['success' => true, 'message' => __('service.attendance.messages.clock_in_success')];
        });
    }

    /**
     * Handle employee clock-out.
     */
    public function clockOut(Employee $employee): array
    {
        return DB::transaction(function () use ($employee) {
            $attendance = Attendance::where('employee_id', $employee->id)
                ->whereNotNull('clock_in') 
                ->whereNull('clock_out')
                ->latest('clock_in')
                ->first();

            if (! $attendance) {
                return ['success' => false, 'message' => __('service.attendance.messages.no_active_attendance')];
            }

            $now = now();
            $schedule = $attendance->schedule;
            $message = __('service.attendance.messages.clock_out_success');

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
                    $message = __('service.attendance.messages.clock_out_early');
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
        $settings = AttendanceSetting::getSettings();

        Attendance::whereNull('clock_out')
            ->where('date', '>=', now()->subDays(2))
            ->with('schedule')
            ->chunkById(100, function ($attendances) use ($settings) {
                foreach ($attendances as $attendance) {
                    if (! $attendance->schedule) {
                        continue;
                    }

                    $shiftStart = Carbon::parse($attendance->date.' '.$attendance->schedule->start_time);
                    $shiftEnd = Carbon::parse($attendance->date.' '.$attendance->schedule->end_time);

                    if ($shiftEnd->lessThan($shiftStart)) {
                        $shiftEnd->addDay();
                    }

                    $cutoffTime = $shiftEnd->copy()->addHours($settings->auto_clock_out_grace_hours);

                    if (now()->greaterThan($cutoffTime)) {
                        DB::transaction(function () use ($attendance, $cutoffTime, $settings) {
                            Fine::create([
                                'employee_id' => $attendance->employee_id,
                                'date' => $attendance->date,
                                'amount' => $settings->no_clock_out_fine_amount,
                                'reason' => __('service.attendance.reasons.forgot_clock_out'),
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
        $settings = AttendanceSetting::getSettings();
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
            ->chunkById(100, function ($employees) use ($dateStr, $settings) {
                foreach ($employees as $employee) {
                    $schedule = $employee->schedules->first();

                    if (! $schedule) {
                        continue;
                    }

                    if ($employee->attendances->isNotEmpty()) {
                        continue;
                    }

                    $onLeave = $employee->leaveRequests->isNotEmpty();

                    DB::transaction(function () use ($employee, $schedule, $dateStr, $onLeave, $settings) {
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
                            'amount' => $settings->absent_fine_amount,
                            'reason' => __('service.attendance.reasons.absent'),
                        ]);
                    });
                }
            });
    }
}
