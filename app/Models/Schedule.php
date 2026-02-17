<?php

namespace App\Models;

use App\Enums\DaysOfWeek;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id',
        'shift_id',
        'day_of_week',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => DaysOfWeek::class,
        ];
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
