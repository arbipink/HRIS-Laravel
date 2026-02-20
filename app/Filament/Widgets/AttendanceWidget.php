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

    public function notifyError(string $message): void
    {
        Notification::make()
            ->title($message)
            ->danger()
            ->send();
    }

    public function clockIn(AttendanceService $service, $lat = null, $lng = null)
    {
        $user = Auth::user();

        if (! $user->employee) {
            Notification::make()->title('Employee record not found.')->danger()->send();

            return;
        }

        if (! $lat || ! $lng) {
            Notification::make()->title('Location data is required to clock in.')->danger()->send();

            return;
        }

        $workLocation = \App\Models\WorkLocation::where('is_active', true)->first();

        if (! $workLocation) {
            Notification::make()->title('No active work location found.')->danger()->send();

            return;
        }

        if (! $workLocation->isWithinRadius($lat, $lng)) {
            Notification::make()
                ->title('You are outside the valid work area.')
                ->danger()
                ->send();

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

    public function clockOut(AttendanceService $service, $lat = null, $lng = null)
    {
        $user = Auth::user();

        if (! $user->employee) {
            Notification::make()->title('Employee record not found.')->danger()->send();

            return;
        }

        if (! $lat || ! $lng) {
            Notification::make()->title('Location data is required to clock out.')->danger()->send();

            return;
        }

        $workLocation = \App\Models\WorkLocation::where('is_active', true)->first();

        if (! $workLocation) {
            Notification::make()->title('No active work location found.')->danger()->send();

            return;
        }

        if (! $workLocation->isWithinRadius($lat, $lng)) {
            Notification::make()
                ->title('You are outside the valid work area.')
                ->danger()
                ->send();

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
