<?php

namespace App\Filament\Widgets;

use App\Enums\EmployeeRole;
use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\Fine;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        /** @var User $user */
        $user = Auth::user();
        $isCompanyWide = $user->hasRole(EmployeeRole::ADMIN) || $user->hasRole(EmployeeRole::HRD);
        $employeeId = $user->employee?->id;

        return [
            $this->getMonthlyFinesStat($isCompanyWide, $employeeId),
            $this->getPendingLeaveRequestsStat($user),
            $this->getRemainingLeaveStat($isCompanyWide, $user->employee),
            $this->getUpcomingHolidaysStat(),
        ];
    }

    protected function getMonthlyFinesStat(bool $isCompanyWide, ?int $employeeId): Stat
    {
        $currentMonthQuery = Fine::query()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year);

        $lastMonthQuery = Fine::query()
            ->whereMonth('date', now()->subMonth()->month)
            ->whereYear('date', now()->subMonth()->year);

        if (! $isCompanyWide) {
            $currentMonthQuery->where('employee_id', $employeeId);
            $lastMonthQuery->where('employee_id', $employeeId);
        }

        $currentTotal = $currentMonthQuery->sum('amount');
        $lastTotal = $lastMonthQuery->sum('amount');

        $diff = $currentTotal - $lastTotal;
        $isIncrease = $diff > 0;

        $stat = Stat::make(__('widget.stats.fines.title'), 'Rp '.number_format($currentTotal, 0, ',', '.'))
            ->description($isIncrease ? __('widget.stats.fines.increased') : __('widget.stats.fines.decreased'))
            ->descriptionIcon($isIncrease ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($isIncrease ? 'danger' : 'success');

        if ($diff === 0) {
            $stat->description(__('widget.stats.fines.same'))
                ->descriptionIcon('heroicon-m-minus')
                ->color('gray');
        }

        return $stat;
    }

    protected function getPendingLeaveRequestsStat(User $user): Stat
    {
        $employee = $user->employee;
        $role = $employee?->role;
        $query = LeaveRequest::query();

        $description = __('widget.stats.leave.pending.awaiting');

        if ($role === EmployeeRole::ADMIN) {
            $query->where('status', LeaveStatus::PENDING);
            $description = __('widget.stats.leave.pending.company_wide');
        } elseif ($role === EmployeeRole::HRD) {
            $query->where('manager_status', LeaveStatus::APPROVED)
                ->where('hrd_status', LeaveStatus::PENDING);
            $description = __('widget.stats.leave.pending.hr_approval');
        } elseif ($role === EmployeeRole::MANAGER) {
            $query->where('manager_status', LeaveStatus::PENDING)
                ->whereHas('employee', fn ($q) => $q->where('department_id', $employee->department_id));
            $description = __('widget.stats.leave.pending.dept_approval');
        } else {
            $query->where('employee_id', $employee?->id)
                ->where('status', LeaveStatus::PENDING);
            $description = __('widget.stats.leave.pending.your_requests');
        }

        $count = $query->count();

        return Stat::make(__('widget.stats.leave.pending.title'), $count)
            ->description($description)
            ->descriptionIcon('heroicon-m-clock')
            ->color($count > 0 ? 'warning' : 'gray');
    }

    protected function getRemainingLeaveStat(bool $isCompanyWide, ?Employee $employee): Stat
    {
        if ($isCompanyWide) {
            $average = Employee::query()->avg('remaining_leave_days') ?? 0;

            return Stat::make(__('widget.stats.leave.remaining.avg_title'), round($average, 1).' '.__('widget.stats.leave.remaining.days'))
                ->description(__('widget.stats.leave.remaining.avg_description'))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info');
        }

        $remaining = $employee?->remaining_leave_days ?? 0;

        return Stat::make(__('widget.stats.leave.remaining.your_title'), $remaining.' '.__('widget.stats.leave.remaining.days'))
            ->description(__('widget.stats.leave.remaining.your_description'))
            ->descriptionIcon('heroicon-m-calendar-days')
            ->color($remaining > 5 ? 'success' : ($remaining > 0 ? 'warning' : 'danger'));
    }

    protected function getUpcomingHolidaysStat(): Stat
    {
        $now = now();
        $endOfMonth = now()->endOfMonth();

        $upcomingHolidays = Holiday::query()
            ->where('date', '>=', $now->toDateString())
            ->where('date', '<=', $endOfMonth->toDateString())
            ->orderBy('date', 'asc')
            ->get();

        $count = $upcomingHolidays->count();
        $nearestHoliday = $upcomingHolidays->first();

        $description = __('widget.stats.holidays.none');
        if ($nearestHoliday) {
            $date = Carbon::parse($nearestHoliday->date)->format('M j');
            $description = __('widget.stats.holidays.next', ['name' => $nearestHoliday->name, 'date' => $date]);
        }

        return Stat::make(__('widget.stats.holidays.title'), $count)
            ->description($description)
            ->descriptionIcon('heroicon-m-calendar')
            ->color('primary');
    }
}
