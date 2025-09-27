<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'icon_path',
        'icon_updated_at',
        'status',
        'version',
        'billing_frequency',
        'client_id',
        'currency_id',
        'project_owner_id',
        'project_manager_id',
        'parent_project_id',
        'project_end_date',
    ];

    protected $casts = [
        'icon_updated_at' => 'datetime',
        'project_end_date' => 'date',
    ];

    /**
     * Get the parent project.
     */
    public function parentProject(): BelongsTo
    {
        return $this->belongsTo(ParentProject::class, 'parent_project_id');
    }

    /**
     * Get the child projects.
     */
    public function childProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'parent_project_id');
    }

    /**
     * Get the project owner.
     */
    public function projectOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_owner_id');
    }

    /**
     * Get the project manager.
     */
    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'project_manager_id');
    }

    /**
     * Get the project resources.
     */
    public function projectResources(): HasMany
    {
        return $this->hasMany(ProjectResource::class);
    }

    /**
     * Get the modules for this project.
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    /**
     * Get the timesheet entries for this project.
     */
    public function timesheetEntries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class);
    }

    /**
     * Get the cards for this project.
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
