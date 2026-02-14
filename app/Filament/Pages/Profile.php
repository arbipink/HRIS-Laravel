<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
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
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Identity Details')
                    ->description('Update your personal account information.')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(table: 'users', ignorable: Auth::user()),

                        Select::make('gender')
                            ->options([
                                'PRIA' => 'PRIA',
                                'WANITA' => 'WANITA',
                            ])
                            ->native(false),
                    ])->columns(2),

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
                    ]),
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

        $user->employee()->updateOrCreate(
            ['user_id' => $user->id],
            ['gender' => $data['gender']]
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
