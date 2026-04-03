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
        return match ($this) {
            self::PRESENT => __('enums.attendance_status.present'),
            self::LATE => __('enums.attendance_status.late'),
            self::ABSENT => __('enums.attendance_status.absent'),
            self::SICK => __('enums.attendance_status.sick'),
            self::LEAVE => __('enums.attendance_status.leave'),
            self::EARLY_LEAVE => __('enums.attendance_status.early_leave'),
        };
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
