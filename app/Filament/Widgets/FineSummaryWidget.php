<?php

namespace App\Filament\Widgets;

use App\Enums\EmployeeRole;
use App\Models\Fine;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class FineSummaryWidget extends Widget
{
    protected string $view = 'filament.widgets.fine-summary-widget';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 2;

    public function getViewData(): array
    {
        $user = Auth::user();
        $query = Fine::query()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year);

        if (! $user->hasRole(EmployeeRole::ADMIN) && ! $user->hasRole(EmployeeRole::HRD)) {
            $query->where('employee_id', $user->employee?->id);
        }

        $totalFines = $query->sum('amount');
        $countFines = $query->count();

        return [
            'totalFines' => $totalFines,
            'countFines' => $countFines,
            'isIndividual' => ! $user->hasRole(EmployeeRole::ADMIN) && ! $user->hasRole(EmployeeRole::HRD),
        ];
    }
}
