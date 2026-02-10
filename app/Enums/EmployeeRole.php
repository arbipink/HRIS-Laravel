<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EmployeeRole: string implements HasColor, HasLabel
{
    case ADMIN = 'ADMIN';
    case HRD = 'HRD';
    case MANAGER = 'MANAGER';
    case EMPLOYEE = 'EMPLOYEE';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::HRD => 'HR Department',
            self::MANAGER => 'Manager',
            self::EMPLOYEE => 'Staff',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ADMIN => 'danger',
            self::HRD => 'warning',
            self::MANAGER => 'info',
            self::EMPLOYEE => 'gray',
        };
    }
}
