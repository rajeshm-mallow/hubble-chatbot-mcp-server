<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_project_id',
        'name',
        'icon_path',
        'icon_updated_at',
        'status',
        'billing_frequency',
        'client_id',
        'currency_id',
        'project_owner_id',
        'parent_project_end_date',
        'is_internal_project',
    ];

    protected $casts = [
        'icon_updated_at' => 'datetime',
        'parent_project_end_date' => 'date',
    ];

    /**
     * Get the child projects.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'parent_project_id');
    }

    /**
     * Get the parent project resources.
     */
    public function parentProjectResources(): HasMany
    {
        return $this->hasMany(ParentProjectResource::class);
    }

    /**
     * Get the project owner.
     */
    public function projectOwner()
    {
        return $this->belongsTo(User::class, 'project_owner_id');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter active parent projects.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
