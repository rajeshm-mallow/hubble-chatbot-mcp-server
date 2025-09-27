<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimesheetEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'module_id',
        'task_id',
        'description',
        'entry_date',
        'working_hours',
        'approved_hours',
        'authorized_hours',
        'billed_hours',
        'team_id',
        'admin_comments',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'working_hours' => 'decimal:2',
        'approved_hours' => 'decimal:2',
        'authorized_hours' => 'decimal:2',
        'billed_hours' => 'decimal:2',
    ];

    /**
     * Get the user who created this timesheet entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project for this timesheet entry.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the module for this timesheet entry.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the task for this timesheet entry.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the team for this timesheet entry.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by project.
     */
    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to filter by team.
     */
    public function scopeByTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by month.
     */
    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('entry_date', $year)
                    ->whereMonth('entry_date', $month);
    }
}
