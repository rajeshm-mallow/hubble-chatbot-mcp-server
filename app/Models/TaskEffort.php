<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskEffort extends Model
{
    use HasFactory;

    protected $fillable = [
        'efforts',
        'learning',
        'assumption',
        'task_id',
        'module_id',
        'team_id',
        'created_by',
    ];

    protected $casts = [
        'efforts' => 'decimal:2',
        'learning' => 'decimal:2',
    ];

    /**
     * Get the task for this effort.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the module for this effort.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the team for this effort.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who created this effort.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
