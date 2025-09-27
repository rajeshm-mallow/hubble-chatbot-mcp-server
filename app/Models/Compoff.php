<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compoff extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reason',
        'valid_upto',
        'leave_id',
        'created_by',
    ];

    protected $casts = [
        'valid_upto' => 'date',
    ];

    /**
     * Get the user for this compoff.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the leave related to this compoff.
     */
    public function leave(): BelongsTo
    {
        return $this->belongsTo(Leave::class);
    }

    /**
     * Get the user who created this compoff.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
