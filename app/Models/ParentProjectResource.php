<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentProjectResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'parent_project_id',
        'utilisation',
        'removed_on',
        'allocation_from',
        'allocation_to',
        'resource_type',
        'resource_type_comment',
    ];

    protected $casts = [
        'utilisation' => 'integer',
        'removed_on' => 'date',
        'allocation_from' => 'date',
        'allocation_to' => 'date',
    ];

    /**
     * Get the user for this parent project resource.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent project for this resource.
     */
    public function parentProject(): BelongsTo
    {
        return $this->belongsTo(ParentProject::class);
    }
}
