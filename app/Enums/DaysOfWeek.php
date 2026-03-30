<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DaysOfWeek: string implements HasLabel
{
    case MONDAY = 'MONDAY';
    case TUESDAY = 'TUESDAY';
    case WEDNESDAY = 'WEDNESDAY';
    case THURSDAY = 'THURSDAY';
    case FRIDAY = 'FRIDAY';
    case SATURDAY = 'SATURDAY';
    case SUNDAY = 'SUNDAY';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::MONDAY => __('enums.days_of_week.monday'),
            self::TUESDAY => __('enums.days_of_week.tuesday'),
            self::WEDNESDAY => __('enums.days_of_week.wednesday'),
            self::THURSDAY => __('enums.days_of_week.thursday'),
            self::FRIDAY => __('enums.days_of_week.friday'),
            self::SATURDAY => __('enums.days_of_week.saturday'),
            self::SUNDAY => __('enums.days_of_week.sunday'),
        };
    }
}
