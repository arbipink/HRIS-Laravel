<?php

namespace App\Filament\Resources\Employees;

use App\Enums\EmployeeRole;
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

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    public static function getModelLabel(): string
    {
        return __('resource.employee.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.employee.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.organizations');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('resource.employee.sections.work_information'))
                    ->schema([
                        Select::make('user_id')
                            ->label(__('resource.employee.fields.user_id'))
                            ->validationAttribute(__('resource.employee.fields.user_id'))
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('department_id')
                            ->label(__('resource.employee.fields.department_id'))
                            ->validationAttribute(__('resource.employee.fields.department_id'))
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('role')
                            ->label(__('resource.employee.fields.role'))
                            ->validationAttribute(__('resource.employee.fields.role'))
                            ->options(EmployeeRole::class)
                            ->default('EMPLOYEE')
                            ->required(),
                        TextInput::make('remaining_leave_days')
                            ->label(__('resource.employee.fields.remaining_leave_days'))
                            ->validationAttribute(__('resource.employee.fields.remaining_leave_days'))
                            ->required()
                            ->numeric()
                            ->default(8),
                    ])->columns(2),

                Section::make(__('resource.employee.sections.personal_information'))
                    ->schema([
                        Select::make('gender')
                            ->label(__('resource.employee.fields.gender'))
                            ->validationAttribute(__('resource.employee.fields.gender'))
                            ->options([
                                'PRIA' => __('resource.employee.options.gender.male'),
                                'WANITA' => __('resource.employee.options.gender.female'),
                            ])
                            ->required(),
                        TextInput::make('phone_number')
                            ->label(__('resource.employee.fields.phone_number'))
                            ->validationAttribute(__('resource.employee.fields.phone_number'))
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('npwp_number')
                            ->label(__('resource.employee.fields.npwp_number'))
                            ->validationAttribute(__('resource.employee.fields.npwp_number'))
                            ->maxLength(20),
                        TextInput::make('bank_account_number')
                            ->label(__('resource.employee.fields.bank_account_number'))
                            ->validationAttribute(__('resource.employee.fields.bank_account_number'))
                            ->maxLength(30),
                        Textarea::make('address')
                            ->label(__('resource.employee.fields.address'))
                            ->validationAttribute(__('resource.employee.fields.address'))
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make(__('resource.employee.sections.documents_photos'))
                    ->schema([
                        FileUpload::make('pas_photo_path')
                            ->label(__('resource.employee.fields.pas_photo_path'))
                            ->image()
                            ->directory('employee-photos')
                            ->avatar(),
                        FileUpload::make('ktp_photo_path')
                            ->label(__('resource.employee.fields.ktp_photo_path'))
                            ->image()
                            ->directory('employee-docs'),
                        FileUpload::make('kk_photo_path')
                            ->label(__('resource.employee.fields.kk_photo_path'))
                            ->image()
                            ->directory('employee-docs'),
                    ])->columns(3),

                Section::make(__('resource.employee.sections.home_location'))
                    ->schema([
                        TextInput::make('home_latitude')
                            ->label(__('resource.employee.fields.home_latitude'))
                            ->numeric()
                            ->live(onBlur: true),
                        TextInput::make('home_longitude')
                            ->label(__('resource.employee.fields.home_longitude'))
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
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (is_array($state)) {
                                    $set('home_latitude', $state['home_latitude'] ?? null);
                                    $set('home_longitude', $state['home_longitude'] ?? null);
                                }
                            }),
                    ])->columns(2),

                Section::make(__('resource.employee.sections.family_data'))
                    ->schema([
                        Repeater::make('family_data')
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('resource.employee.fields.name'))
                                    ->required(),
                                TextInput::make('relation')
                                    ->label(__('resource.employee.fields.relation'))
                                    ->required(),
                                TextInput::make('phone_number')
                                    ->label(__('resource.employee.fields.phone_number'))
                                    ->tel(),
                                Toggle::make('emergency_contact')
                                    ->label(__('resource.employee.fields.emergency_contact'))
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
                Section::make(__('resource.employee.sections.personal_information'))
                    ->schema([
                        ImageEntry::make('pas_photo_path')
                            ->label(__('resource.employee.fields.photo'))
                            ->circular(),
                        TextEntry::make('user.name')
                            ->label(__('resource.employee.fields.name')),
                        TextEntry::make('phone_number')
                            ->label(__('resource.employee.fields.phone_number'))
                            ->copyable(),
                        TextEntry::make('npwp_number')
                            ->label(__('resource.employee.fields.npwp_number')),
                        TextEntry::make('gender')
                            ->label(__('resource.employee.fields.gender'))
                            ->badge(),
                        TextEntry::make('bank_account_number')
                            ->label(__('resource.employee.fields.bank_account_number')),
                        TextEntry::make('address')
                            ->label(__('resource.employee.fields.address'))
                            ->columnSpanFull(),
                    ])->columns(3),

                Section::make(__('resource.employee.sections.work_details'))
                    ->schema([
                        TextEntry::make('department.name')
                            ->label(__('resource.employee.fields.department_id')),
                        TextEntry::make('role')
                            ->label(__('resource.employee.fields.role'))
                            ->badge(),
                        TextEntry::make('remaining_leave_days')
                            ->label(__('resource.employee.fields.remaining_leave_days'))
                            ->numeric(),
                    ])->columns(3),

                Section::make(__('resource.employee.sections.documents'))
                    ->schema([
                        ImageEntry::make('ktp_photo_path')
                            ->label(__('resource.employee.fields.ktp_photo_path')),
                        ImageEntry::make('kk_photo_path')
                            ->label(__('resource.employee.fields.kk_photo_path')),
                    ])->columns(2),

                Section::make(__('resource.employee.sections.family_data'))
                    ->schema([
                        RepeatableEntry::make('family_data')
                            ->schema([
                                TextEntry::make('name')
                                    ->label(__('resource.employee.fields.name')),
                                TextEntry::make('relation')
                                    ->label(__('resource.employee.fields.relation')),
                                TextEntry::make('phone_number')
                                    ->label(__('resource.employee.fields.phone_number')),
                                IconEntry::make('emergency_contact')
                                    ->label(__('resource.employee.fields.emergency_contact'))
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
                    ->label(__('resource.employee.fields.photo'))
                    ->circular()
                    ->placeholder(__('resource.employee.placeholders.no_data')),
                TextColumn::make('user.name')
                    ->label(__('resource.employee.fields.employee_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label(__('resource.employee.fields.department_id'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone_number')
                    ->label(__('resource.employee.fields.phone_number'))
                    ->searchable()
                    ->placeholder(__('resource.employee.placeholders.no_data')),
                TextColumn::make('role')
                    ->label(__('resource.employee.fields.role'))
                    ->badge(),
                TextColumn::make('gender')
                    ->label(__('resource.employee.fields.gender'))
                    ->badge(),
                TextColumn::make('remaining_leave_days')
                    ->label(__('resource.employee.fields.remaining_leave_days'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('resource.employee.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('resource.employee.fields.updated_at'))
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
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
