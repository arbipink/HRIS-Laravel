<?php

namespace App\Filament\Resources\LeaveRequests\Pages;

use App\Enums\EmployeeRole;
use App\Exports\Sheets\LeaveRequestsSheetExport;
use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageLeaveRequests extends ManageRecords
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadReport')
                ->label(__('actions.download_report', ['label' => __('models.singular.leave_request')]))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => in_array(Auth::user()->employee?->role, [EmployeeRole::ADMIN, EmployeeRole::HRD]))
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new LeaveRequestsSheetExport,
                    'leave-requests-'.now()->format('Y-m-d').'.xlsx'
                )),
            CreateAction::make(),
        ];
    }
}
