<?php

namespace App\Filament\Resources\Fines;

use App\Enums\EmployeeRole;
use App\Filament\Resources\Fines\Pages\ManageFines;
use App\Models\Employee;
use App\Models\Fine;
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

class FineResource extends Resource
{
    protected static ?string $model = Fine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CurrencyDollar;

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.fine');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.management');
    }

    public static function getModelLabel(): string
    {
        return __('models.singular.fine');
    }

    public static function getPluralModelLabel(): string
    {
        return __('models.plural.fine');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || ! $user->employee) {
            return $query->whereRaw('1 = 0');
        }

        $employee = $user->employee;

        if (in_array($employee->role, [EmployeeRole::ADMIN, EmployeeRole::HRD])) {
            return $query;
        }

        if ($employee->role === EmployeeRole::MANAGER) {
            return $query->whereHas('employee', function (Builder $q) use ($employee) {
                $q->where('department_id', $employee->department_id);
            });
        }

        return $query->where('employee_id', $employee->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('employee_id')
                    ->label('Employee')
                    ->options(Employee::with('user')->get()->pluck('user.name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('date')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('reason')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.user.name')
                    ->label('Employee Name'),
                TextEntry::make('date')
                    ->date(),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('reason'),
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
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reason')
                    ->searchable(),
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
            'index' => ManageFines::route('/'),
        ];
    }
}
