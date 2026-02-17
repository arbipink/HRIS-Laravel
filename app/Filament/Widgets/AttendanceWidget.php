<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Models\Attendance;
use App\Models\Fine;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AttendanceWidget extends Widget
{
    protected string $view = 'filament.widgets.attendance-widget';

    public function clockIn()
    {
        $user = Auth::user();

        if (! $user->employee) {
            Notification::make()->title('Employee record not found.')->danger()->send();

            return;
        }

        $todayDate = now()->toDateString();

        $isHoliday = Holiday::where('date', $todayDate)->exists();
        if ($isHoliday) {
            Notification::make()
                ->title('Today is a Holiday')
                ->body('Clock-in is not required today.')
                ->warning()
                ->send();

            return;
        }

        $onLeave = LeaveRequest::where('employee_id', $user->employee->id)
            ->where('status', LeaveStatus::APPROVED)
            ->whereDate('start_date', '<=', $todayDate)
            ->whereDate('end_date', '>=', $todayDate)
            ->exists();

        if ($onLeave) {
            Notification::make()
                ->title('On Leave')
                ->body('You are on approved leave today. Clock-in is disabled.')
                ->warning()
                ->send();

            return;
        }

        $todayStr = strtoupper(now()->format('l'));

        $schedule = Schedule::where('employee_id', $user->employee->id)
            ->where('day_of_week', $todayStr)
            ->first();

        if (! $schedule) {
            Notification::make()->title('No schedule found for today. Cannot clock in.')->danger()->send();

            return;
        }

        $existingAttendance = Attendance::where('employee_id', $user->employee->id)
            ->where('date', $todayDate)
            ->first();

        if ($existingAttendance) {
            Notification::make()->title('You have already clocked in today.')->warning()->send();

            return;
        }

        $now = now();
        $scheduleStartTime = Carbon::parse($schedule->start_time)->setDateFrom($now);
        $earliestClockIn = $scheduleStartTime->copy()->subMinutes(30);

        if ($now->lessThan($earliestClockIn)) {
            Notification::make()
                ->title('Too Early')
                ->body('You can only clock in 30 minutes before your shift starts ('.$scheduleStartTime->format('H:i').').')
                ->warning()
                ->send();

            return;
        }

        $isLate = $now->greaterThan($scheduleStartTime);
        $status = $isLate ? AttendanceStatus::LATE : AttendanceStatus::PRESENT;

        $attendance = Attendance::create([
            'employee_id' => $user->employee->id,
            'schedule_id' => $schedule->id,
            'date' => $now->toDateString(),
            'clock_in' => $now,
            'status' => $status,
        ]);

        if ($isLate) {
            Fine::create([
                'employee_id' => $user->employee->id,
                'date' => $now->toDateString(),
                'amount' => 50000,
                'reason' => 'Late Arrival: Expected '.$scheduleStartTime->format('H:i').', arrived '.$now->format('H:i'),
            ]);

            Notification::make()
                ->title('Clocked In (Late)')
                ->body('You were late. A fine of 50,000 IDR has been recorded.')
                ->danger()
                ->send();
        } else {
            Notification::make()->title('Clocked In Successfully')->success()->send();
        }
    }

    public function clockOut()
    {
        $user = Auth::user();

        $attendance = Attendance::where('employee_id', $user->employee->id)
            ->where('date', now()->toDateString())
            ->first();

        if (! $attendance) {
            Notification::make()->title('No attendance record found for today.')->danger()->send();

            return;
        }

        if ($attendance->clock_out) {
            Notification::make()->title('You have already clocked out.')->warning()->send();

            return;
        }

        $schedule = $attendance->schedule;

        if ($schedule) {
            $now = now();
            $scheduleEndTime = Carbon::parse($schedule->end_time)->setDateFrom($now);
            $earliestClockOut = $scheduleEndTime->copy()->subMinutes(30);

            if ($now->lessThan($earliestClockOut)) {
                Notification::make()
                    ->title('Too Early')
                    ->body('You cannot clock out yet. Earliest clock out is '.$earliestClockOut->format('H:i'))
                    ->warning()
                    ->send();

                return;
            }
        }

        $attendance->update([
            'clock_out' => now(),
        ]);

        Notification::make()->title('Clocked Out Successfully')->success()->send();
    }

    public function getViewData(): array
    {
        $user = Auth::user();
        $todayAttendance = null;

        if ($user && $user->employee) {
            $todayAttendance = Attendance::where('employee_id', $user->employee->id)
                ->where('date', now()->toDateString())
                ->first();
        }

        return [
            'todayAttendance' => $todayAttendance,
            'isEmployee' => (bool) ($user?->employee),
        ];
    }
}
