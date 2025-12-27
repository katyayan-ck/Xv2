<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends BaseModel
{
    protected $table = 'persons';

    protected $fillable = [
        'code',
        'salutation',
        'first_name',
        'middle_name',
        'last_name',
        'display_name',
        'gender',
        'dob',
        'marital_status',
        'marriage_date',
        'spouse_name',
        'no_of_children',
        'fathers_name',
        'mothers_name',
        'occupation',
        'blood_group',
        'nationality',
        'aadhaar_no',
        'pan_no',
        'gst_no',
        'passport_no',
        'mobile_primary',
        'mobile_secondary',
        'email_primary',
        'email_secondary',
        'address_line1',
        'city',
        'state',
        'country',
        'pincode',
        'extra_data',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'dob' => 'date',
        'marriage_date' => 'date',
        'no_of_children' => 'integer',
        'extra_data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ════════════════════════════════════════════════════════

    /**
     * One-to-one relationship to Employee
     * Person is the parent, Employee is child
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class, 'person_id');
    }

    /**
     * One-to-one relationship to User
     * A Person may or may not have a User account
     */
    public function user(): HasOne
    {
        return $this->hasOne(\App\Models\User::class, 'person_id');
    }

    /**
     * Banking details (reusable: Employee, DSA, Customer, etc.)
     */
    public function bankingDetails(): HasMany
    {
        return $this->hasMany(PersonBankingDetail::class, 'person_id');
    }

    // ════════════════════════════════════════════════════════
    // SCOPES
    // ════════════════════════════════════════════════════════

    public function scopeSearch($query, string $term)
    {
        return $query->where('first_name', 'like', "%$term%")
            ->orWhere('last_name', 'like', "%$term%")
            ->orWhere('email_primary', 'like', "%$term%")
            ->orWhere('mobile_primary', 'like', "%$term%")
            ->orWhere('pan_no', strtoupper($term));
    }

    // ════════════════════════════════════════════════════════
    // ACCESSORS
    // ════════════════════════════════════════════════════════

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }
}
