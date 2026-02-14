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
        // Replaces underscores with spaces and capitalizes
        return str($this->value)->replace('_', ' ')->title();
    }
}
