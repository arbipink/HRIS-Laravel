<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AttendanceStatus: string implements HasColor, HasLabel
{
    case PRESENT = 'PRESENT';
    case LATE = 'LATE';
    case ABSENT = 'ABSENT';
    case SICK = 'SICK';
    case LEAVE = 'LEAVE';
    case EARLY_LEAVE = 'EARLY_LEAVE';

    public function getLabel(): ?string
    {
        return ucfirst(strtolower(str_replace('_', ' ', $this->value)));
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PRESENT => 'success',
            self::LATE => 'warning',
            self::ABSENT => 'danger',
            self::SICK => 'info',
            self::LEAVE => 'gray',
            self::EARLY_LEAVE => 'warning',
        };
    }
}
