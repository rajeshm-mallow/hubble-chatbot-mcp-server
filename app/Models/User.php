<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'employee_id',
        'email',
        'password',
        'username',
        'name',
        'is_employed',
        'status',
        'team_id',
        'branch_id',
        'designation_id',
        'team_owner',
        'first_name',
        'last_name',
        'is_saturday_working',
        'eligibility_status',
        'active_status',
        'personal_email',
        'ctc_category_id',
        'work_schedule_id',
        'gmi_set_id',
        'previous_status',
        'insurance',
        'is_earned_leave_credited',
        'earned_leave_credited_date',
        'notice_period_duration',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_employed' => 'boolean',
            'is_saturday_working' => 'boolean',
            'team_owner' => 'boolean',
            'is_earned_leave_credited' => 'boolean',
            'earned_leave_credited_date' => 'date',
            'insurance' => 'decimal:2',
        ];
    }

    /**
     * Get the team that the user belongs to.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the branch that the user belongs to.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the designation of the user.
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    /**
     * Get the user details.
     */
    public function userDetails(): BelongsTo
    {
        return $this->belongsTo(UserDetail::class);
    }

    /**
     * Get the roles assigned to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
                    ->withPivot('user_type');
    }

    /**
     * Get the reporting persons for this user.
     */
    public function reportingPersons(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_reporting_persons', 'user_id', 'reporting_person_id');
    }

    /**
     * Get the users who report to this user.
     */
    public function subordinates(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_reporting_persons', 'reporting_person_id', 'user_id');
    }

    /**
     * Get the leaves for the user.
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * Get the timeoffs for the user.
     */
    public function timeoffs(): HasMany
    {
        return $this->hasMany(Timeoff::class);
    }

    /**
     * Get the leave balances for the user.
     */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /**
     * Get the timesheet entries for the user.
     */
    public function timesheetEntries(): HasMany
    {
        return $this->hasMany(TimesheetEntry::class);
    }

    /**
     * Get the project resources for the user.
     */
    public function projectResources(): HasMany
    {
        return $this->hasMany(ProjectResource::class);
    }

    /**
     * Get the parent project resources for the user.
     */
    public function parentProjectResources(): HasMany
    {
        return $this->hasMany(ParentProjectResource::class);
    }

    /**
     * Get the cards assigned to the user.
     */
    public function cards(): BelongsToMany
    {
        return $this->belongsToMany(Card::class, 'cards_users', 'user_id', 'card_id')
                    ->withPivot('designation_id');
    }

    /**
     * Get the cards suggested by the user.
     */
    public function suggestedCards(): HasMany
    {
        return $this->hasMany(Card::class, 'suggested_id');
    }

    /**
     * Get the cards issued by the user.
     */
    public function issuedCards(): HasMany
    {
        return $this->hasMany(Card::class, 'issuer_id');
    }

    /**
     * Scope to filter active users.
     */
    public function scopeActive($query)
    {
        return $query->where('active_status', 'active');
    }

    /**
     * Scope to filter employed users.
     */
    public function scopeEmployed($query)
    {
        return $query->where('is_employed', true);
    }

    /**
     * Scope to search users by name or employee ID.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('employee_id', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%");
        });
    }
}