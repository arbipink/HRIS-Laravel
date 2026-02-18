<?php

namespace App\Filament\Pages;

use App\Models\AttendanceSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class ManageAttendanceSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static ?string $navigationLabel = 'Attendance Settings';

    protected static ?string $title = 'Attendance Settings';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected string $view = 'filament.pages.manage-attendance-settings';

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(Gate::allows('manage_settings'), 403);

        $settings = AttendanceSetting::getSettings();

        $this->form->fill($settings->toArray());
    }

    public static function canAccess(): bool
    {
        return Gate::allows('manage_settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Fine Amounts')
                    ->description('Configure the fine amounts for various attendance violations.')
                    ->schema([
                        TextInput::make('late_fine_amount')
                            ->label('Late Fine Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0),

                        TextInput::make('absent_fine_amount')
                            ->label('Absent Fine Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0),

                        TextInput::make('no_clock_out_fine_amount')
                            ->label('No Clock-Out Fine Amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0),
                    ])->columns(3),

                Section::make('Grace Periods')
                    ->description('Configure the grace periods for attendance.')
                    ->schema([
                        TextInput::make('grace_period_minutes')
                            ->label('Grace Period (Minutes)')
                            ->numeric()
                            ->suffix('minutes')
                            ->required()
                            ->minValue(0),

                        TextInput::make('auto_clock_out_grace_hours')
                            ->label('Auto Clock-Out Grace (Hours)')
                            ->numeric()
                            ->suffix('hours')
                            ->required()
                            ->minValue(0),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        abort_unless(Gate::allows('manage_settings'), 403);

        $data = $this->form->getState();

        AttendanceSetting::getSettings()->update($data);

        Notification::make()
            ->success()
            ->title('Settings updated successfully')
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
