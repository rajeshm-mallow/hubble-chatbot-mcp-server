<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Card extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'suggestion_status',
        'issuing_status',
        'type',
        'job_description',
        'content',
        'rejected_reason',
        'delay_reason',
        'suggested_id',
        'issuer_id',
        'suggested_at',
        'issued_at',
        'reminder_date',
        'escalation_date',
    ];

    protected $casts = [
        'suggested_at' => 'date',
        'issued_at' => 'date',
        'reminder_date' => 'date',
        'escalation_date' => 'date',
    ];

    /**
     * Get the project this card belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who suggested this card.
     */
    public function suggestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suggested_id');
    }

    /**
     * Get the user who issued this card.
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issuer_id');
    }

    /**
     * Get the users assigned to this card.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'cards_users', 'card_id', 'user_id')
                    ->withPivot('designation_id');
    }

    /**
     * Scope to filter by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by suggestion status.
     */
    public function scopeBySuggestionStatus($query, $status)
    {
        return $query->where('suggestion_status', $status);
    }

    /**
     * Scope to filter by issuing status.
     */
    public function scopeByIssuingStatus($query, $status)
    {
        return $query->where('issuing_status', $status);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('issued_at', [$startDate, $endDate]);
    }
}
