<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Enums\EmployeeRole;
use App\Exports\Sheets\EmployeesSheetExport;
use App\Filament\Resources\Employees\EmployeeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadReport')
                ->label(__('actions.download_report', ['label' => __('models.plural.employee')]))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => in_array(Auth::user()->employee?->role, [EmployeeRole::ADMIN, EmployeeRole::HRD]))
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new EmployeesSheetExport,
                    'employees-'.now()->format('Y-m-d').'.xlsx'
                )),
            CreateAction::make(),
        ];
    }
}
