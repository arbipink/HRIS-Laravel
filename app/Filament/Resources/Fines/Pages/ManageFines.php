<?php

namespace App\Filament\Resources\Fines\Pages;

use App\Enums\EmployeeRole;
use App\Exports\Sheets\FinesSheetExport;
use App\Filament\Resources\Fines\FineResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
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
                ->form([
                    DatePicker::make('from_date')
                        ->label(__('fields.from_date'))
                        ->default(now()->subDays(30)->toDateString()),
                    DatePicker::make('to_date')
                        ->label(__('fields.to_date'))
                        ->default(now()->toDateString()),
                ])
                ->action(fn (array $data): BinaryFileResponse => Excel::download(
                    new FinesSheetExport($data['from_date'], $data['to_date']),
                    'fines-'.now()->format('Y-m-d').'.xlsx'
                )),
            CreateAction::make(),
        ];
    }
}
