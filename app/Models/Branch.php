<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'street',
        'city',
        'state',
        'country',
        'pincode',
        'latitude',
        'longitude',
        'type',
        'percentage',
        'check_in_time',
        'professional_tax',
    ];

    protected $casts = [
        'pincode' => 'decimal:2',
        'latitude' => 'decimal:8,6',
        'longitude' => 'decimal:8,6',
        'percentage' => 'integer',
        'check_in_time' => 'datetime:H:i:s',
        'professional_tax' => 'array',
    ];

    /**
     * Get the users in this branch.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
