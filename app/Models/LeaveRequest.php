<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
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

    protected $casts = [
        'type' => LeaveType::class,
        'status' => LeaveStatus::class,
        'manager_status' => LeaveStatus::class,
        'hrd_status' => LeaveStatus::class,
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function hrd()
    {
        return $this->belongsTo(Employee::class, 'hrd_id');
    }
}
