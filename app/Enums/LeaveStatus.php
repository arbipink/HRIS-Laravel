<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum LeaveStatus: string implements HasColor, HasIcon, HasLabel
{
    case PENDING = 'PENDING';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => __('enums.leave_status.pending'),
            self::APPROVED => __('enums.leave_status.approved'),
            self::REJECTED => __('enums.leave_status.rejected'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-m-clock',
            self::APPROVED => 'heroicon-m-check-circle',
            self::REJECTED => 'heroicon-m-x-circle',
        };
    }
}
