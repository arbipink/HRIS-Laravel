<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fine extends Model
{
    use HasFactory;

    const LATE_FINE = 50000;

    const ABSENT_FINE = 100000;

    const NO_CLOCK_OUT_FINE = 50000;

    const GRACE_PERIOD_MINUTES = 30;

    const AUTO_CLOCK_OUT_GRACE_HOURS = 12;

    protected $fillable = ['employee_id', 'date', 'amount', 'reason'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
