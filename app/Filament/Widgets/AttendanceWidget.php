<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Schedule;
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

        $existingAttendance = Attendance::where('employee_id', $user->employee->id)
            ->where('date', now()->toDateString())
            ->first();

        if ($existingAttendance) {
            Notification::make()->title('You have already clocked in today.')->warning()->send();

            return;
        }

        $todayStr = strtoupper(now()->format('l'));

        $schedule = Schedule::where('employee_id', $user->employee->id)
            ->where('day_of_week', $todayStr)
            ->first();

        Attendance::create([
            'employee_id' => $user->employee->id,
            'schedule_id' => $schedule?->id,
            'date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => AttendanceStatus::PRESENT,
        ]);
        Notification::make()->title('Clocked In Successfully')->success()->send();
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
