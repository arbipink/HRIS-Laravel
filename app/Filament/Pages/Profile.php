<?php

namespace App\Filament\Pages;

use BackedEnum;
use EduardoRibeiroDev\FilamentLeaflet\Fields\MapPicker;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserCircle;

    public function getTitle(): string
    {
        return __('page.profile.title');
    }

    protected string $view = 'filament.pages.profile';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $employee = $user->employee;
        $lat = (float) ($employee?->home_latitude ?? -6.2088);
        $lng = (float) ($employee?->home_longitude ?? 106.8456);

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'gender' => $employee?->gender,
            'phone_number' => $employee?->phone_number,
            'npwp_number' => $employee?->npwp_number,
            'bank_account_number' => $employee?->bank_account_number,
            'address' => $employee?->address,
            'ktp_photo_path' => $employee?->ktp_photo_path,
            'kk_photo_path' => $employee?->kk_photo_path,
            'pas_photo_path' => $employee?->pas_photo_path,
            'home_latitude' => $employee?->home_latitude,
            'home_longitude' => $employee?->home_longitude,
            'family_data' => $employee?->family_data,
            'location' => [
                'home_latitude' => $lat,
                'home_longitude' => $lng,
            ],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('page.profile.sections.account.title'))
                    ->description(__('page.profile.sections.account.description'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('page.profile.fields.name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label(__('page.profile.fields.email'))
                            ->email()
                            ->required()
                            ->unique(table: 'users', ignorable: Auth::user()),
                    ])->columns(2),

                Section::make(__('page.profile.sections.personal.title'))
                    ->description(__('page.profile.sections.personal.description'))
                    ->schema([
                        Select::make('gender')
                            ->label(__('resource.employee.fields.gender'))
                            ->options([
                                'PRIA' => __('resource.employee.options.gender.male'),
                                'WANITA' => __('resource.employee.options.gender.female'),
                            ])
                            ->native(false),
                        TextInput::make('phone_number')
                            ->label(__('resource.employee.fields.phone_number'))
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('npwp_number')
                            ->label(__('resource.employee.fields.npwp_number'))
                            ->maxLength(20),
                        TextInput::make('bank_account_number')
                            ->label(__('resource.employee.fields.bank_account_number'))
                            ->maxLength(30),
                        Textarea::make('address')
                            ->label(__('resource.employee.fields.address'))
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make(__('page.profile.sections.documents.title'))
                    ->description(__('page.profile.sections.documents.description'))
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

                Section::make(__('page.profile.sections.location.title'))
                    ->description(__('page.profile.sections.location.description'))
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
                            ->center(
                                (float) (Auth::user()->employee?->home_latitude ?? -6.2088),
                                (float) (Auth::user()->employee?->home_longitude ?? 106.8456)
                            )
                            ->zoom(11)
                            ->autoCenter()
                            ->columnSpanFull()
                            ->latitudeFieldName('home_latitude') // Custom key
                            ->longitudeFieldName('home_longitude') // Custom key
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (is_array($state)) {
                                    // Read from the custom keys the map is now using
                                    $set('home_latitude', $state['home_latitude'] ?? null);
                                    $set('home_longitude', $state['home_longitude'] ?? null);
                                }
                            }),
                    ])->columns(2),

                Section::make(__('page.profile.sections.family.title'))
                    ->description(__('page.profile.sections.family.description'))
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

                Section::make(__('page.profile.sections.password.title'))
                    ->description(__('page.profile.sections.password.description'))
                    ->schema([
                        TextInput::make('new_password')
                            ->label(__('page.profile.fields.new_password'))
                            ->password()
                            ->rule(Password::default())
                            ->autocomplete('new-password')
                            ->dehydrated(false),

                        TextInput::make('new_password_confirmation')
                            ->label(__('page.profile.fields.confirm_password'))
                            ->password()
                            ->same('new_password')
                            ->requiredWith('new_password')
                            ->dehydrated(false),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (! empty($data['new_password'])) {
            $userData['password'] = Hash::make($data['new_password']);
        }

        $user->update($userData);

        $employeeData = [
            'gender' => $data['gender'],
            'phone_number' => $data['phone_number'],
            'npwp_number' => $data['npwp_number'],
            'bank_account_number' => $data['bank_account_number'],
            'address' => $data['address'],
            'ktp_photo_path' => $data['ktp_photo_path'],
            'kk_photo_path' => $data['kk_photo_path'],
            'pas_photo_path' => $data['pas_photo_path'],
            'home_latitude' => $data['home_latitude'],
            'home_longitude' => $data['home_longitude'],
            'family_data' => $data['family_data'],
        ];

        $user->employee()->updateOrCreate(
            ['user_id' => $user->id],
            $employeeData
        );

        Notification::make()
            ->success()
            ->title(__('page.profile.notifications.saved'))
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('page.profile.actions.save'))
                ->submit('save'),
        ];
    }
}
