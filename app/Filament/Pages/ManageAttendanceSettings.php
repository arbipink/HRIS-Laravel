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

class ManageAttendanceSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    public static function getNavigationLabel(): string
    {
        return __('navigation.labels.attendance_settings');
    }

    public function getTitle(): string
    {
        return __('navigation.labels.attendance_settings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.settings');
    }

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
                Section::make(__('page.attendance_settings.sections.fine_amounts.title'))
                    ->description(__('page.attendance_settings.sections.fine_amounts.description'))
                    ->schema([
                        TextInput::make('late_fine_amount')
                            ->label(__('page.attendance_settings.fields.late_fine_amount'))
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0),

                        TextInput::make('absent_fine_amount')
                            ->label(__('page.attendance_settings.fields.absent_fine_amount'))
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0),

                        TextInput::make('no_clock_out_fine_amount')
                            ->label(__('page.attendance_settings.fields.no_clock_out_fine_amount'))
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0),
                    ])->columns(3),

                Section::make(__('page.attendance_settings.sections.grace_periods.title'))
                    ->description(__('page.attendance_settings.sections.grace_periods.description'))
                    ->schema([
                        TextInput::make('grace_period_minutes')
                            ->label(__('page.attendance_settings.fields.grace_period_minutes'))
                            ->numeric()
                            ->suffix(__('page.attendance_settings.fields.minutes'))
                            ->required()
                            ->minValue(0),

                        TextInput::make('auto_clock_out_grace_hours')
                            ->label(__('page.attendance_settings.fields.auto_clock_out_grace_hours'))
                            ->numeric()
                            ->suffix(__('page.attendance_settings.fields.hours'))
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
            ->title(__('page.attendance_settings.notifications.saved'))
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
