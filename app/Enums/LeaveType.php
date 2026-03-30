<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LeaveType: string implements HasLabel
{
    case ANNUAL = 'ANNUAL';
    case MATERNITY = 'MATERNITY';
    case MISCARRIAGE = 'MISCARRIAGE';
    case MENSTRUAL = 'MENSTRUAL';
    case SICK = 'SICK';
    case MARRIAGE = 'MARRIAGE';
    case PATERNITY = 'PATERNITY';
    case BEREAVEMENT = 'BEREAVEMENT';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ANNUAL => __('enums.leave_type.annual'),
            self::MATERNITY => __('enums.leave_type.maternity'),
            self::MISCARRIAGE => __('enums.leave_type.miscarriage'),
            self::MENSTRUAL => __('enums.leave_type.menstrual'),
            self::SICK => __('enums.leave_type.sick'),
            self::MARRIAGE => __('enums.leave_type.marriage'),
            self::PATERNITY => __('enums.leave_type.paternity'),
            self::BEREAVEMENT => __('enums.leave_type.bereavement'),
        };
    }
}
