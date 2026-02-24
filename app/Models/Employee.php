<?php

namespace App\Models;

use App\Enums\EmployeeRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'department_id',
        'role',
        'gender',
        'ktp_photo_path',
        'kk_photo_path',
        'npwp_number',
        'pas_photo_path',
        'phone_number',
        'address',
        'home_latitude',
        'home_longitude',
        'bank_account_number',
        'family_data',
        'remaining_leave_days',
    ];

    protected function casts(): array
    {
        return [
            'role' => EmployeeRole::class,
            'family_data' => 'array',
            'home_latitude' => 'float',
            'home_longitude' => 'float',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
