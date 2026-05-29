<?php

namespace App\Filament\Resources\Attendances;

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeRole;
use App\Filament\Resources\Attendances\Pages\ManageAttendances;
use App\Models\Attendance;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CheckBadge;

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.attendance');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.operations');
    }

    public static function getModelLabel(): string
    {
        return __('models.singular.attendance');
    }

    public static function getPluralModelLabel(): string
    {
        return __('models.plural.attendance');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if (! $user || ! $user->employee) {
            return $query;
        }

        $employee = $user->employee;

        if ($employee->role === EmployeeRole::MANAGER) {
            return $query->whereHas('employee', function (Builder $q) use ($employee) {
                $q->where('department_id', $employee->department_id);
            });
        }

        if ($employee->role === EmployeeRole::EMPLOYEE) {
            return $query->where('employee_id', $employee->id);
        }

        return $query;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.user.name')
                    ->label(__('resource.attendance.fields.employee')),
                TextEntry::make('date')
                    ->label(__('resource.attendance.fields.date'))
                    ->date(),
                TextEntry::make('clock_in')
                    ->label(__('resource.attendance.fields.clock_in'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('clock_out')
                    ->label(__('resource.attendance.fields.clock_out'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->label(__('resource.attendance.fields.status'))
                    ->badge(),
                TextEntry::make('notes')
                    ->label(__('resource.attendance.fields.notes'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->label(__('resource.attendance.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('resource.attendance.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.user.name')
                    ->label(__('resource.attendance.fields.employee'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label(__('resource.attendance.fields.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('clock_in')
                    ->label(__('resource.attendance.fields.clock_in'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('clock_out')
                    ->label(__('resource.attendance.fields.clock_out'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('resource.attendance.fields.status'))
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('resource.attendance.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('resource.attendance.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('resource.attendance.fields.status'))
                    ->options(AttendanceStatus::class)
                    ->multiple()
                    ->default([
                        AttendanceStatus::LATE->value,
                        AttendanceStatus::ABSENT->value,
                        AttendanceStatus::LEAVE->value,
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAttendances::route('/'),
        ];
    }
}
