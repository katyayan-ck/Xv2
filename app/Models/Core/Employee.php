<?php

namespace App\Models\Core;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Employee Model
 * 
 * Represents the employment relationship
 * All employment-specific data belongs here
 */
class Employee extends BaseModel
{
    protected $table = 'employees';

    protected $fillable = [
        'code',
        'employee_code',
        'person_id',
        'designation_id',
        'primary_branch_id',
        'primary_department_id',
        'reporting_manager_id',
        'joining_date',
        'resignation_date',
        'employment_type',
        'employment_status',
        'is_active',
        'ome_id',
        'biometric_id',
        'shift_type',
        'shift_name',
        'late_arrival_window',
        'early_going_window',
        'leave_rule',
        'week_off',
        'wo_work_compensation',
        'comp_off_applicable',
        'salary_structure_type',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'resignation_date' => 'date',
        'is_active' => 'boolean',
        'late_arrival_window' => 'integer',
        'early_going_window' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ════════════════════════════════════════════════════════

    /**
     * Person (biological identity)
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /**
     * Designation
     */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }

    /**
     * Primary Branch
     */
    public function primaryBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'primary_branch_id');
    }

    /**
     * Primary Department
     */
    public function primaryDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'primary_department_id');
    }

    /**
     * Reporting Manager (self-referential)
     */
    public function reportingManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporting_manager_id');
    }

    /**
     * Subordinates (employees reporting to this employee)
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'reporting_manager_id');
    }

    /**
     * Employment History (temporal audit)
     */
    public function employmentHistory(): HasMany
    {
        return $this->hasMany(EmploymentHistory::class, 'employee_id');
    }

    /**
     * Benefits & Compliance Data
     */
    public function benefits(): HasOne
    {
        return $this->hasOne(EmployeeBenefits::class, 'employee_id');
    }

    /**
     * Branch assignments (pivot: from_date, to_date, is_current, is_primary)
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(
            Branch::class,
            'employee_branch_assignments',
            'employee_id',
            'branch_id'
        )->withPivot(['from_date', 'to_date', 'is_primary', 'is_current', 'created_by', 'updated_by']);
    }

    /**
     * Department assignments
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(
            Department::class,
            'employee_department_assignments',
            'employee_id',
            'department_id'
        )->withPivot(['from_date', 'to_date', 'is_current', 'created_by', 'updated_by']);
    }

    /**
     * Location assignments
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(
            Location::class,
            'employee_location_assignments',
            'employee_id',
            'location_id'
        )->withPivot(['from_date', 'to_date', 'is_current', 'created_by', 'updated_by']);
    }

    /**
     * Vertical assignments
     */
    public function verticals(): BelongsToMany
    {
        return $this->belongsToMany(
            Vertical::class,
            'employee_vertical_assignments',
            'employee_id',
            'vertical_id'
        )->withPivot(['from_date', 'to_date', 'is_current', 'created_by', 'updated_by']);
    }

    /**
     * Post assignments
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(
            Post::class,
            'employee_post_assignments',
            'employee_id',
            'post_id'
        )->withPivot(['from_date', 'to_date', 'is_current', 'assignment_order', 'remarks', 'created_by', 'updated_by']);
    }

    // ════════════════════════════════════════════════════════
    // METHODS
    // ════════════════════════════════════════════════════════

    /**
     * Get current scope (branches, departments, locations, verticals)
     * Used for RBAC filtering
     */
    public function getCurrentScope(): array
    {
        return [
            'branches' => $this->branches()->wherePivot('is_current', true)->pluck('branches.id')->toArray(),
            'departments' => $this->departments()->wherePivot('is_current', true)->pluck('departments.id')->toArray(),
            'locations' => $this->locations()->wherePivot('is_current', true)->pluck('locations.id')->toArray(),
            'verticals' => $this->verticals()->wherePivot('is_current', true)->pluck('verticals.id')->toArray(),
        ];
    }

    /**
     * Get all subordinates (direct + indirect)
     */
    public function getDownlineReports(): array
    {
        $reports = [];

        foreach ($this->subordinates as $subordinate) {
            $reports[] = $subordinate->id;
            $reports = array_merge($reports, $subordinate->getDownlineReports());
        }

        return array_unique($reports);
    }

    // ════════════════════════════════════════════════════════
    // SCOPES
    // ════════════════════════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('employment_status', 'Active');
    }

    // ════════════════════════════════════════════════════════
    // STATIC METHODS
    // ════════════════════════════════════════════════════════

    public static function generateCode(string $prefix = 'EMP'): string
    {
        $lastId = self::withTrashed()->max('id') ?? 0;
        return $prefix . '-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }
}
