<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Services\AttendanceService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AttendanceWidget extends Widget
{
    protected string $view = 'filament.widgets.attendance-widget';

    public function clockIn(AttendanceService $service)
    {
        $user = Auth::user();

        if (! $user->employee) {
            Notification::make()->title('Employee record not found.')->danger()->send();

            return;
        }

        $result = $service->clockIn($user->employee);

        if ($result['success']) {
            Notification::make()
                ->title($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title($result['message'])
                ->warning()
                ->send();
        }
    }

    public function clockOut(AttendanceService $service)
    {
        $user = Auth::user();

        if (! $user->employee) {
            Notification::make()->title('Employee record not found.')->danger()->send();

            return;
        }

        $result = $service->clockOut($user->employee);

        if ($result['success']) {
            Notification::make()
                ->title($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title($result['message'])
                ->danger()
                ->send();
        }
    }

    public function getViewData(): array
    {
        $user = Auth::user();
        $activeAttendance = null;

        if ($user && $user->employee) {
            // Find the most recent attendance that hasn't been clocked out yet,
            // or today's attendance even if clocked out.
            $activeAttendance = Attendance::where('employee_id', $user->employee->id)
                ->where(function ($query) {
                    $query->whereNull('clock_out')
                        ->orWhere('date', now()->toDateString());
                })
                ->latest('clock_in')
                ->first();
        }

        return [
            'activeAttendance' => $activeAttendance,
            'isEmployee' => (bool) ($user?->employee),
        ];
    }
}
