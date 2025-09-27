<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardsUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_id',
        'user_id',
        'designation_id',
    ];

    /**
     * Get the card for this user assignment.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * Get the user for this card assignment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the designation for this card assignment.
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }
}
