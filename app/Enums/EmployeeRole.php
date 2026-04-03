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
            self::ADMIN => __('enums.employee_role.admin'),
            self::HRD => __('enums.employee_role.hrd'),
            self::MANAGER => __('enums.employee_role.manager'),
            self::EMPLOYEE => __('enums.employee_role.employee'),
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
