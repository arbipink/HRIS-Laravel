<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'type',
        'reason',
        'start_date',
        'end_date',
        'attachment_path',
        'status',
        'manager_status',
        'hrd_status',
        'manager_id',
        'hrd_id',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
