<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'late_fine_amount',
        'absent_fine_amount',
        'no_clock_out_fine_amount',
        'grace_period_minutes',
        'auto_clock_out_grace_hours',
    ];

    /**
     * Get the singleton settings instance.
     */
    public static function getSettings(): self
    {
        return self::first() ?? self::create([
            'late_fine_amount' => 50000,
            'absent_fine_amount' => 100000,
            'no_clock_out_fine_amount' => 50000,
            'grace_period_minutes' => 30,
            'auto_clock_out_grace_hours' => 12,
        ]);
    }
}
