<?php

namespace App\Filament\Resources\Schedules;

use App\Enums\DaysOfWeek;
use App\Enums\EmployeeRole;
use App\Filament\Resources\Schedules\Pages\ManageSchedules;
use App\Models\Employee;
use App\Models\Schedule;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.schedule');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.operations');
    }

    public static function getModelLabel(): string
    {
        return __('models.singular.schedule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('models.plural.schedule');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user->employee) {
            return $query->whereRaw('1 = 0');
        }

        $role = $user->employee->role;

        if (in_array($role, [EmployeeRole::ADMIN, EmployeeRole::HRD])) {
            return $query;
        }

        if ($role === EmployeeRole::MANAGER) {
            return $query->whereHas('employee', function (Builder $q) use ($user) {
                $q->where('department_id', $user->employee->department_id);
            });
        }

        return $query->where('employee_id', $user->employee->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label(__('resource.schedule.fields.employee'))
                    ->options(Employee::with('user')->get()->pluck('user.name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('day_of_week')
                    ->label(__('resource.schedule.fields.day_of_week'))
                    ->options(DaysOfWeek::class)
                    ->required(),
                TimePicker::make('start_time')
                    ->label(__('resource.schedule.fields.start_time'))
                    ->required(),
                TimePicker::make('end_time')
                    ->label(__('resource.schedule.fields.end_time'))
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.user.name')
                    ->label(__('resource.schedule.fields.employee'))
                    ->numeric(),
                TextEntry::make('day_of_week')
                    ->label(__('resource.schedule.fields.day_of_week'))
                    ->badge(),
                TextEntry::make('start_time')
                    ->label(__('resource.schedule.fields.start_time'))
                    ->time(),
                TextEntry::make('end_time')
                    ->label(__('resource.schedule.fields.end_time'))
                    ->time(),
                TextEntry::make('created_at')
                    ->label(__('resource.schedule.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('resource.schedule.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.user.name')
                    ->label(__('resource.schedule.fields.employee'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('day_of_week')
                    ->label(__('resource.schedule.fields.day_of_week'))
                    ->badge(),
                TextColumn::make('start_time')
                    ->label(__('resource.schedule.fields.start_time'))
                    ->time()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label(__('resource.schedule.fields.end_time'))
                    ->time()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('resource.schedule.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('resource.schedule.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('update_times')
                        ->label(__('actions.update_schedule_times'))
                        ->icon(Heroicon::Clock)
                        ->schema([
                            TimePicker::make('start_time')
                                ->required(),
                            TimePicker::make('end_time')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each(fn (Schedule $record) => $record->update([
                                'start_time' => $data['start_time'],
                                'end_time' => $data['end_time'],
                            ]));
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSchedules::route('/'),
        ];
    }
}
