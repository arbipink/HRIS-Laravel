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

    protected string $view = 'filament.pages.profile';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        $employee = $user->employee;

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
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Account Information')
                    ->description('Update your account login details.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(table: 'users', ignorable: Auth::user()),
                    ])->columns(2),

                Section::make('Personal Details')
                    ->description('Update your personal information.')
                    ->schema([
                        Select::make('gender')
                            ->options([
                                'PRIA' => 'PRIA',
                                'WANITA' => 'WANITA',
                            ])
                            ->native(false),
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
                    ->description('Upload your identity documents and photos.')
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
                    ->description('Specify your home location on the map.')
                    ->schema([
                        TextInput::make('home_latitude')
                            ->numeric()
                            ->live(onBlur: true),
                        TextInput::make('home_longitude')
                            ->numeric()
                            ->live(onBlur: true),
                        MapPicker::make('location')
                            ->height(300)
                            ->center(Auth::user()->employee?->home_latitude ?? -6.2088, Auth::user()->employee?->home_longitude ?? 106.8456)
                            ->zoom(11)
                            ->autoCenter()
                            ->columnSpanFull()
                            ->latitudeFieldName('home_latitude')
                            ->longitudeFieldName('home_longitude')
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if (is_array($state)) {
                                    $set('home_latitude', $state['lat'] ?? $state['latitude'] ?? null);
                                    $set('home_longitude', $state['lng'] ?? $state['longitude'] ?? null);
                                }
                            }),
                    ])->columns(2),

                Section::make('Family Data')
                    ->description('Add your family members information.')
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

                Section::make('Update Password')
                    ->description('Leave empty if you do not want to change your password.')
                    ->schema([
                        TextInput::make('new_password')
                            ->label('New Password')
                            ->password()
                            ->rule(Password::default())
                            ->autocomplete('new-password')
                            ->dehydrated(false),

                        TextInput::make('new_password_confirmation')
                            ->label('Confirm New Password')
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
            ->title('Profile updated successfully')
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('Save Changes'))
                ->submit('save'),
        ];
    }
}
