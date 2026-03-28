<?php

namespace App\Filament\Resources\Fines\Pages;

use App\Enums\EmployeeRole;
use App\Exports\Sheets\FinesSheetExport;
use App\Filament\Resources\Fines\FineResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManageFines extends ManageRecords
{
    protected static string $resource = FineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadReport')
                ->label(__('actions.download_report', ['label' => __('models.singular.fine')]))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (): bool => in_array(Auth::user()->employee?->role, [EmployeeRole::ADMIN, EmployeeRole::HRD]))
                ->action(fn (): BinaryFileResponse => Excel::download(
                    new FinesSheetExport,
                    'fines-'.now()->format('Y-m-d').'.xlsx'
                )),
            CreateAction::make(),
        ];
    }
}
