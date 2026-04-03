<?php

namespace App\Filament\Resources\LeaveRequests\Pages;

use App\Enums\EmployeeRole;
use App\Exports\Sheets\LeaveRequestsSheetExport;
use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
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
                ->form([
                    DatePicker::make('from_date')
                        ->label(__('fields.from_date'))
                        ->default(now()->subDays(30)->toDateString()),
                    DatePicker::make('to_date')
                        ->label(__('fields.to_date'))
                        ->default(now()->toDateString()),
                ])
                ->action(fn (array $data): BinaryFileResponse => Excel::download(
                    new LeaveRequestsSheetExport($data['from_date'], $data['to_date']),
                    'leave-requests-'.now()->format('Y-m-d').'.xlsx'
                )),
            CreateAction::make(),
        ];
    }
}
