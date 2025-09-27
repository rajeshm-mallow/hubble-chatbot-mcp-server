<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lop',
        'action',
        'earned_leaves',
        'casual_leaves',
        'sick_leaves',
        'updated_by',
        'leave_id',
        'lop_categories',
    ];

    protected $casts = [
        'lop' => 'decimal:2',
        'earned_leaves' => 'decimal:2',
        'casual_leaves' => 'decimal:2',
        'sick_leaves' => 'decimal:2',
        'lop_categories' => 'array',
    ];

    /**
     * Get the user for this leave balance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who updated this balance.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the leave related to this balance.
     */
    public function leave(): BelongsTo
    {
        return $this->belongsTo(Leave::class);
    }
}
