<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'reporting_person_id',
        'resource_type',
        'utilisation',
        'charge_by_hour',
        'primary_project',
        'allotted_from',
        'removed_on',
        'position',
        'parent_project_id',
        'project_owner',
        'allotted_to',
        'resource_type_comment',
        'designation_id',
    ];

    protected $casts = [
        'utilisation' => 'integer',
        'charge_by_hour' => 'integer',
        'primary_project' => 'boolean',
        'project_owner' => 'boolean',
        'allotted_from' => 'date',
        'removed_on' => 'date',
        'allotted_to' => 'date',
        'position' => 'integer',
    ];

    /**
     * Get the user for this project resource.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project for this resource.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the reporting person for this resource.
     */
    public function reportingPerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporting_person_id');
    }

    /**
     * Get the parent project for this resource.
     */
    public function parentProject(): BelongsTo
    {
        return $this->belongsTo(ParentProject::class, 'parent_project_id');
    }

    /**
     * Get the designation for this resource.
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }
}
