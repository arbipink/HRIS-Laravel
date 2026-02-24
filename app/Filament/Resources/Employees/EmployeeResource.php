<?php

namespace App\Filament\Resources\Employees;

use App\Enums\EmployeeRole;
use App\Filament\Resources\Employees\Pages\ManageEmployees;
use App\Models\Employee;
use BackedEnum;
use EduardoRibeiroDev\FilamentLeaflet\Fields\MapPicker;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Organizations';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Work Information')
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('role')
                            ->options(EmployeeRole::class)
                            ->default('EMPLOYEE')
                            ->required(),
                        TextInput::make('remaining_leave_days')
                            ->required()
                            ->numeric()
                            ->default(8),
                    ])->columns(2),

                Section::make('Personal Information')
                    ->schema([
                        Select::make('gender')
                            ->options(['PRIA' => 'Pria', 'WANITA' => 'Wanita'])
                            ->required(),
                        TextInput::make('phone_number')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('npwp_number')
                            ->label('NPWP Number')
                            ->maxLength(20),
                        TextInput::make('bank_account_number')
                            ->maxLength(30),
                        Textarea::make('address')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Documents & Photos')
                    ->schema([
                        FileUpload::make('pas_photo_path')
                            ->label('Pas Photo (4x6)')
                            ->image()
                            ->directory('employee-photos')
                            ->avatar(),
                        FileUpload::make('ktp_photo_path')
                            ->label('KTP Photo')
                            ->image()
                            ->directory('employee-docs'),
                        FileUpload::make('kk_photo_path')
                            ->label('Family Card (KK) Photo')
                            ->image()
                            ->directory('employee-docs'),
                    ])->columns(3),

                Section::make('Home Location')
                    ->schema([
                        TextInput::make('home_latitude')
                            ->numeric()
                            ->live(onBlur: true),
                        TextInput::make('home_longitude')
                            ->numeric()
                            ->live(onBlur: true),
                        MapPicker::make('location')
                            ->height(300)
                            ->center(-6.2088, 106.8456)
                            ->zoom(11)
                            ->autoCenter()
                            ->columnSpanFull()
                            ->latitudeFieldName('home_latitude')
                            ->longitudeFieldName('home_longitude')
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (is_array($state)) {
                                    $set('home_latitude', $state['lat'] ?? $state['latitude'] ?? null);
                                    $set('home_longitude', $state['lng'] ?? $state['longitude'] ?? null);
                                }
                            }),
                    ])->columns(2),

                Section::make('Family Data')
                    ->schema([
                        Repeater::make('family_data')
                            ->schema([
                                TextInput::make('name')
                                    ->required(),
                                TextInput::make('relation')
                                    ->required(),
                                TextInput::make('phone_number')
                                    ->tel(),
                                Toggle::make('emergency_contact')
                                    ->label('Emergency Contact')
                                    ->inline(false),
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->schema([
                        ImageEntry::make('pas_photo_path')
                            ->label('Photo')
                            ->circular(),
                        TextEntry::make('user.name')
                            ->label('Name'),
                        TextEntry::make('phone_number')
                            ->copyable(),
                        TextEntry::make('npwp_number')
                            ->label('NPWP'),
                        TextEntry::make('gender')
                            ->badge(),
                        TextEntry::make('bank_account_number')
                            ->label('Bank Account'),
                        TextEntry::make('address')
                            ->columnSpanFull(),
                    ])->columns(3),

                Section::make('Work Details')
                    ->schema([
                        TextEntry::make('department.name'),
                        TextEntry::make('role')
                            ->badge(),
                        TextEntry::make('remaining_leave_days')
                            ->numeric(),
                    ])->columns(3),

                Section::make('Documents')
                    ->schema([
                        ImageEntry::make('ktp_photo_path')
                            ->label('KTP Photo'),
                        ImageEntry::make('kk_photo_path')
                            ->label('KK Photo'),
                    ])->columns(2),

                Section::make('Family Data')
                    ->schema([
                        RepeatableEntry::make('family_data')
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('relation'),
                                TextEntry::make('phone_number'),
                                IconEntry::make('emergency_contact')
                                    ->boolean(),
                            ])->columns(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('pas_photo_path')
                    ->label('Photo')
                    ->circular()
                    ->placeholder('-'),
                TextColumn::make('user.name')
                    ->label('Employee Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone_number')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('role')
                    ->badge(),
                TextColumn::make('gender')
                    ->badge(),
                TextColumn::make('remaining_leave_days')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                // TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEmployees::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
