<?php

namespace App\Filament\Resources\LeaveRequests;

use App\Enums\EmployeeRole;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Filament\Resources\LeaveRequests\Pages\ManageLeaveRequests;
use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
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

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || ! $user->employee) {
            return $query;
        }

        $employee = $user->employee;

        if ($employee->role === EmployeeRole::EMPLOYEE) {
            return $query->where('employee_id', $employee->id);
        }

        if ($employee->role === EmployeeRole::MANAGER) {
            return $query->whereHas('employee', function (Builder $q) use ($employee) {
                $q->where('department_id', $employee->department_id);
            });
        }

        return $query;
    }

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
                    ->badge(),
                TextColumn::make('hrd_status')
                    ->badge(),
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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLeaveRequests::route('/'),
        ];
    }
}
