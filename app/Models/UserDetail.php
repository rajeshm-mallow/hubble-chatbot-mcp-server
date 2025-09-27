<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_pic_path',
        'profile_pic_updated_at',
        'date_of_birth',
        'date_of_joining_as_intern',
        'intern_period',
        'date_of_joining',
        'aadhar_no',
        'blood_group',
        'gender',
        'religion',
        'notify_birthday',
        'notify_joining_anniversary',
        'notify_timesheet_entry',
        'pan_no',
        'bank_account_no',
        'ifsc_code',
        'pf_account_no',
        'uan',
        'date_of_relieved',
        'pan_proof',
        'aadhar_proof',
        'bank_name',
        'date_of_end_as_intern',
        'date_of_end_as_probation',
        'nationality',
        'blood_group_others',
        'last_working_date_of_notice_period',
        'data',
        'date_of_joining_as_project_intern',
        'date_of_end_as_project_intern',
        'date_of_on_hold',
        'on_hold_end_date',
    ];

    protected $casts = [
        'profile_pic_updated_at' => 'datetime',
        'date_of_birth' => 'date',
        'date_of_joining_as_intern' => 'date',
        'intern_period' => 'decimal:2',
        'date_of_joining' => 'date',
        'notify_birthday' => 'boolean',
        'notify_joining_anniversary' => 'boolean',
        'notify_timesheet_entry' => 'boolean',
        'date_of_relieved' => 'date',
        'date_of_end_as_intern' => 'date',
        'date_of_end_as_probation' => 'date',
        'last_working_date_of_notice_period' => 'date',
        'date_of_joining_as_project_intern' => 'date',
        'date_of_end_as_project_intern' => 'date',
        'date_of_on_hold' => 'date',
        'on_hold_end_date' => 'date',
        'data' => 'array',
    ];

    /**
     * Get the user for these details.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
