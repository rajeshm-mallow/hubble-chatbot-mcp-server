<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'required_reporting_person',
        'referral_bonus',
    ];

    protected $casts = [
        'required_reporting_person' => 'boolean',
        'referral_bonus' => 'integer',
    ];

    /**
     * Get the users with this designation.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the project resources with this designation.
     */
    public function projectResources(): HasMany
    {
        return $this->hasMany(ProjectResource::class);
    }

    /**
     * Get the cards users with this designation.
     */
    public function cardsUsers(): HasMany
    {
        return $this->hasMany(CardsUser::class);
    }
}
