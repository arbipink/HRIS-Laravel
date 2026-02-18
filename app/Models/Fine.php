<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fine extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'date', 'amount', 'reason'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
