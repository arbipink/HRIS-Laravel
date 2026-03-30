<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Enums\DaysOfWeek;
use App\Filament\Resources\Schedules\ScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageSchedules extends ManageRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [];

        $tabs['all'] = Tab::make(__('All Schedules'));

        foreach (DaysOfWeek::cases() as $day) {
            $tabs[$day->value] = Tab::make($day->getLabel())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('day_of_week', $day));
        }

        return $tabs;
    }
}
