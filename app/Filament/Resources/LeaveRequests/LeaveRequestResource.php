<?php

namespace App\Filament\Resources\LeaveRequests;

use App\Enums\EmployeeRole;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Filament\Resources\LeaveRequests\Pages\ManageLeaveRequests;
use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowRightOnRectangle;

    protected static string|UnitEnum|null $navigationGroup = 'Management';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('employee_id')
                    ->required()
                    ->numeric(),
                Select::make('type')
                    ->options(LeaveType::class)
                    ->default('ANNUAL')
                    ->required(),
                TextInput::make('reason')
                    ->required(),
                DatePicker::make('start_date')
                    ->required(),
                DatePicker::make('end_date')
                    ->required(),
                TextInput::make('attachment_path'),
                Select::make('status')
                    ->options(LeaveStatus::class)
                    ->default('PENDING')
                    ->required(),
                Select::make('manager_status')
                    ->options(LeaveStatus::class)
                    ->default('PENDING')
                    ->required(),
                TextInput::make('manager_id')
                    ->numeric(),
                Select::make('hrd_status')
                    ->options(LeaveStatus::class)
                    ->default('PENDING')
                    ->required(),
                TextInput::make('hrd_id')
                    ->numeric(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.user.name')
                    ->label('Employee'),
                TextEntry::make('type')
                    ->badge(),
                TextEntry::make('reason'),
                TextEntry::make('start_date')
                    ->date(),
                TextEntry::make('end_date')
                    ->date(),
                TextEntry::make('attachment_path')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('manager_status')
                    ->badge(),
                TextEntry::make('manager.user.name')
                    ->label('Manager')
                    ->placeholder('-'),
                TextEntry::make('hrd_status')
                    ->badge(),
                TextEntry::make('hrd.user.name')
                    ->label('HRD')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();

                if (! $user || ! $user->employee) {
                    return $query;
                }

                $employee = $user->employee;

                if ($employee->role === EmployeeRole::EMPLOYEE) {
                    return $query->where('employee_id', $employee->id);
                }

                if ($employee->role === EmployeeRole::MANAGER) {
                    return $query->where('manager_status', LeaveStatus::PENDING);
                }

                if ($employee->role === EmployeeRole::HRD) {
                    return $query->where('hrd_status', LeaveStatus::PENDING)
                        ->where('manager_status', LeaveStatus::APPROVED);
                }

                return $query;
            })
            ->columns([
                TextColumn::make('employee.user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('manager_status')
                    ->badge()
                    ->visible(fn () => in_array(Auth::user()?->employee?->role, [EmployeeRole::MANAGER, EmployeeRole::EMPLOYEE])),
                TextColumn::make('hrd_status')
                    ->badge()
                    ->visible(fn () => in_array(Auth::user()?->employee?->role, [EmployeeRole::HRD, EmployeeRole::EMPLOYEE])),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (LeaveRequest $record) => $record->status === LeaveStatus::PENDING && $record->manager_status === LeaveStatus::PENDING && $record->hrd_status === LeaveStatus::PENDING),
                DeleteAction::make()
                    ->visible(fn (LeaveRequest $record) => $record->status === LeaveStatus::PENDING && $record->manager_status === LeaveStatus::PENDING && $record->hrd_status === LeaveStatus::PENDING),
                Action::make('approve')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->action(function (LeaveRequest $record) {
                        $employee = Auth::user()?->employee;

                        if ($employee->role === EmployeeRole::MANAGER) {
                            $record->manager_status = LeaveStatus::APPROVED;
                            $record->manager_id = $employee->id;
                        } elseif ($employee->role === EmployeeRole::HRD) {
                            $record->hrd_status = LeaveStatus::APPROVED;
                            $record->hrd_id = $employee->id;
                        }

                        if ($record->manager_status === LeaveStatus::APPROVED && $record->hrd_status === LeaveStatus::APPROVED) {
                            $record->status = LeaveStatus::APPROVED;
                        }

                        $record->save();

                        Notification::make()
                            ->title('Leave request approved successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(function () {
                        $employee = Auth::user()?->employee;
                        if (! $employee) {
                            return false;
                        }

                        return in_array($employee->role, [EmployeeRole::MANAGER, EmployeeRole::HRD]);
                    }),
                Action::make('reject')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->action(function (LeaveRequest $record) {
                        $employee = Auth::user()?->employee;

                        if ($employee->role === EmployeeRole::MANAGER) {
                            $record->manager_status = LeaveStatus::REJECTED;
                            $record->manager_id = $employee->id;
                        } elseif ($employee->role === EmployeeRole::HRD) {
                            $record->hrd_status = LeaveStatus::REJECTED;
                            $record->hrd_id = $employee->id;
                        }

                        $record->status = LeaveStatus::REJECTED;
                        $record->save();

                        Notification::make()
                            ->title('Leave request rejected successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(function () {
                        $employee = Auth::user()?->employee;
                        if (! $employee) {
                            return false;
                        }

                        return in_array($employee->role, [EmployeeRole::MANAGER, EmployeeRole::HRD]);
                    }),
            ])
            ->toolbarActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLeaveRequests::route('/'),
        ];
    }
}
