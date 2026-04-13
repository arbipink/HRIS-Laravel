<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\WorkLocation;
use App\Services\AttendanceService;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceWidget extends Widget
{
    protected string $view = 'filament.widgets.attendance-widget';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 1;

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
            Notification::make()->title(__('widget.attendance.notifications.employee_not_found'))->danger()->send();

            return;
        }

        if (! $lat || ! $lng) {
            Notification::make()->title(__('widget.attendance.notifications.location_required_in'))->danger()->send();

            return;
        }

        $workLocation = WorkLocation::where('is_active', true)->first();

        if (! $workLocation) {
            Notification::make()->title(__('widget.attendance.notifications.no_active_location'))->danger()->send();

            return;
        }

        if (! $workLocation->isWithinRadius($lat, $lng)) {
            Notification::make()
                ->title(__('widget.attendance.notifications.outside_area'))
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
            Notification::make()->title(__('widget.attendance.notifications.employee_not_found'))->danger()->send();

            return;
        }

        if (! $lat || ! $lng) {
            Notification::make()->title(__('widget.attendance.notifications.location_required_out'))->danger()->send();

            return;
        }

        $workLocation = WorkLocation::where('is_active', true)->first();

        if (! $workLocation) {
            Notification::make()->title(__('widget.attendance.notifications.no_active_location'))->danger()->send();

            return;
        }

        if (! $workLocation->isWithinRadius($lat, $lng)) {
            Notification::make()
                ->title(__('widget.attendance.notifications.outside_area'))
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
        $isEarly = false;

        if ($user && $user->employee) {
            // Find the most recent attendance that hasn't been clocked out yet,
            // or today's attendance even if clocked out.
            $activeAttendance = Attendance::where('employee_id', $user->employee->id)
                ->whereNotNull('clock_in')
                ->where(function ($query) {
                    $query->whereNull('clock_out')
                        ->orWhere('date', now()->toDateString());
                })
                ->latest('clock_in')
                ->first();

            if ($activeAttendance && ! $activeAttendance->clock_out && $activeAttendance->schedule) {
                $now = now();
                $schedule = $activeAttendance->schedule;
                $scheduleEndTime = Carbon::parse($schedule->end_time);
                $clockInDate = Carbon::parse($activeAttendance->date);

                $scheduleStartTime = Carbon::parse($schedule->start_time);
                if ($scheduleEndTime->lessThan($scheduleStartTime)) {
                    $scheduleEndTime = $scheduleEndTime->setDateFrom($clockInDate)->addDay();
                } else {
                    $scheduleEndTime = $scheduleEndTime->setDateFrom($clockInDate);
                }

                $isEarly = $now->lessThan($scheduleEndTime);
            }
        }

        return [
            'activeAttendance' => $activeAttendance,
            'isEmployee' => (bool) ($user?->employee),
            'isEarly' => $isEarly,
        ];
    }
}
