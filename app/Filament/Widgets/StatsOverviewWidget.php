<?php

namespace App\Filament\Widgets;

use App\Enums\EmployeeRole;
use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\Fine;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        /** @var \App\Models\User $user */
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

        $stat = Stat::make('Monthly Fines', 'Rp '.number_format($currentTotal, 0, ',', '.'))
            ->description($isIncrease ? 'Increased from last month' : 'Decreased from last month')
            ->descriptionIcon($isIncrease ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
            ->color($isIncrease ? 'danger' : 'success');

        if ($diff === 0) {
            $stat->description('Same as last month')
                ->descriptionIcon('heroicon-m-minus')
                ->color('gray');
        }

        return $stat;
    }

    protected function getPendingLeaveRequestsStat(\App\Models\User $user): Stat
    {
        $employee = $user->employee;
        $role = $employee?->role;
        $query = LeaveRequest::query();

        $description = 'Awaiting action';

        if ($role === EmployeeRole::ADMIN) {
            $query->where('status', LeaveStatus::PENDING);
            $description = 'Total pending company-wide';
        } elseif ($role === EmployeeRole::HRD) {
            $query->where('manager_status', LeaveStatus::APPROVED)
                ->where('hrd_status', LeaveStatus::PENDING);
            $description = 'Pending your HR approval';
        } elseif ($role === EmployeeRole::MANAGER) {
            $query->where('manager_status', LeaveStatus::PENDING)
                ->whereHas('employee', fn ($q) => $q->where('department_id', $employee->department_id));
            $description = 'Pending your dept approval';
        } else {
            $query->where('employee_id', $employee?->id)
                ->where('status', LeaveStatus::PENDING);
            $description = 'Your pending requests';
        }

        $count = $query->count();

        return Stat::make('Pending Leave Requests', $count)
            ->description($description)
            ->descriptionIcon('heroicon-m-clock')
            ->color($count > 0 ? 'warning' : 'gray');
    }

    protected function getRemainingLeaveStat(bool $isCompanyWide, ?Employee $employee): Stat
    {
        if ($isCompanyWide) {
            $average = Employee::query()->avg('remaining_leave_days') ?? 0;

            return Stat::make('Avg. Remaining Leave', round($average, 1).' Days')
                ->description('Across all employees')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info');
        }

        $remaining = $employee?->remaining_leave_days ?? 0;

        return Stat::make('Your Remaining Leave', $remaining.' Days')
            ->description('Annual leave balance')
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

        $description = 'No more holidays this month';
        if ($nearestHoliday) {
            $date = \Carbon\Carbon::parse($nearestHoliday->date)->format('M j');
            $description = "Next: {$nearestHoliday->name} ({$date})";
        }

        return Stat::make('Holidays This Month', $count)
            ->description($description)
            ->descriptionIcon('heroicon-m-calendar')
            ->color('primary');
    }
}
