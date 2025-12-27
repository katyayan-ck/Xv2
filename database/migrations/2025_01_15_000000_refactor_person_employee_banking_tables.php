<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. ENHANCE PERSONS TABLE
        Schema::table('persons', function (Blueprint $table) {
            if (!Schema::hasColumn('persons', 'blood_group')) {
                $table->string('blood_group')->nullable()->after('occupation');
            }
            if (!Schema::hasColumn('persons', 'nationality')) {
                $table->string('nationality')->default('Indian')->after('blood_group');
            }
            if (!Schema::hasColumn('persons', 'marriage_date')) {
                $table->date('marriage_date')->nullable()->after('marital_status');
            }
            if (!Schema::hasColumn('persons', 'no_of_children')) {
                $table->integer('no_of_children')->default(0)->after('marriage_date');
            }
            if (!Schema::hasColumn('persons', 'fathers_name')) {
                $table->string('fathers_name')->nullable()->after('no_of_children');
            }
            if (!Schema::hasColumn('persons', 'mothers_name')) {
                $table->string('mothers_name')->nullable()->after('fathers_name');
            }
            if (!Schema::hasColumn('persons', 'passport_no')) {
                $table->string('passport_no')->nullable()->unique()->after('gst_no');
            }
            if (!Schema::hasColumn('persons', 'address_line1')) {
                $table->string('address_line1')->nullable()->after('extra_data');
            }
            if (!Schema::hasColumn('persons', 'city')) {
                $table->string('city')->nullable()->after('address_line1');
            }
            if (!Schema::hasColumn('persons', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('persons', 'country')) {
                $table->string('country')->default('India')->after('state');
            }
            if (!Schema::hasColumn('persons', 'pincode')) {
                $table->string('pincode')->nullable()->after('country');
            }

            // Add indexes if not exist (Laravel 11+ native methods)
            $indexes = Schema::getIndexes('persons');
            $indexNames = array_column($indexes, 'name');

            if (!in_array('persons_email_primary_index', $indexNames)) {
                $table->index('email_primary', 'persons_email_primary_index');
            }
            if (!in_array('persons_mobile_primary_index', $indexNames)) {
                $table->index('mobile_primary', 'persons_mobile_primary_index');
            }
            if (!in_array('persons_pan_no_index', $indexNames)) {
                $table->index('pan_no', 'persons_pan_no_index');
            }
        });

        // 2. ENHANCE EMPLOYEES TABLE
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'employee_code')) {
                $table->string('employee_code')->unique()->after('code');
            }
            if (!Schema::hasColumn('employees', 'reporting_manager_id')) {
                $table->unsignedBigInteger('reporting_manager_id')->nullable()->after('primary_department_id');
            }
            if (!Schema::hasColumn('employees', 'ome_id')) {
                $table->string('ome_id')->nullable()->after('employment_status');
            }
            if (!Schema::hasColumn('employees', 'biometric_id')) {
                $table->string('biometric_id')->nullable()->unique()->after('ome_id');
            }
            if (!Schema::hasColumn('employees', 'shift_type')) {
                $table->string('shift_type')->nullable()->after('biometric_id');
            }
            if (!Schema::hasColumn('employees', 'shift_name')) {
                $table->string('shift_name')->nullable()->after('shift_type');
            }
            if (!Schema::hasColumn('employees', 'late_arrival_window')) {
                $table->integer('late_arrival_window')->default(0)->comment('minutes')->after('shift_name');
            }
            if (!Schema::hasColumn('employees', 'early_going_window')) {
                $table->integer('early_going_window')->default(0)->comment('minutes')->after('late_arrival_window');
            }
            if (!Schema::hasColumn('employees', 'leave_rule')) {
                $table->string('leave_rule')->nullable()->after('early_going_window');
            }
            if (!Schema::hasColumn('employees', 'week_off')) {
                $table->string('week_off')->nullable()->after('leave_rule');
            }
            if (!Schema::hasColumn('employees', 'wo_work_compensation')) {
                $table->string('wo_work_compensation')->nullable()->after('week_off');
            }
            if (!Schema::hasColumn('employees', 'comp_off_applicable')) {
                $table->string('comp_off_applicable')->nullable()->after('wo_work_compensation');
            }
            if (!Schema::hasColumn('employees', 'salary_structure_type')) {
                $table->string('salary_structure_type')->nullable()->after('comp_off_applicable');
            }

            // Add foreign key if not exists
            $foreignKeys = Schema::getForeignKeys('employees');
            $hasReportingFK = collect($foreignKeys)->contains(
                fn($fk) =>
                $fk['columns'] === ['reporting_manager_id'] &&
                    $fk['foreign_table'] === 'employees' &&
                    $fk['foreign_columns'] === ['id']
            );

            if (!$hasReportingFK) {
                $table->foreign('reporting_manager_id')
                    ->references('id')
                    ->on('employees')
                    ->onDelete('set null');
            }
        });

        // 3. REFACTOR PERSON_BANKING_DETAILS
        Schema::table('person_banking_details', function (Blueprint $table) {
            if (!Schema::hasColumn('person_banking_details', 'person_id')) {
                $table->unsignedBigInteger('person_id')->after('id');
            }

            $foreignKeys = Schema::getForeignKeys('person_banking_details');
            $hasPersonFK = collect($foreignKeys)->contains(
                fn($fk) =>
                $fk['columns'] === ['person_id'] &&
                    $fk['foreign_table'] === 'persons' &&
                    $fk['foreign_columns'] === ['id']
            );

            if (!$hasPersonFK) {
                $table->foreign('person_id')->references('id')->on('persons')->onDelete('cascade');
            }

            if (!Schema::hasColumn('person_banking_details', 'bank_name')) {
                $table->string('bank_name')->nullable();
            }
            if (!Schema::hasColumn('person_banking_details', 'account_number')) {
                $table->string('account_number')->nullable();
            }
            if (!Schema::hasColumn('person_banking_details', 'ifsc_code')) {
                $table->string('ifsc_code')->nullable();
            }
            if (!Schema::hasColumn('person_banking_details', 'account_holder_name')) {
                $table->string('account_holder_name')->nullable();
            }
            if (!Schema::hasColumn('person_banking_details', 'salary_payment_mode')) {
                $table->string('salary_payment_mode')->nullable();
            }
        });

        // 4. CREATE EMPLOYEE_BENEFITS TABLE
        Schema::create('employee_benefits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->unique();
            $table->string('pf_eligible')->nullable();
            $table->string('pf_registration_type')->nullable();
            $table->string('employee_pf_number')->nullable();
            $table->string('employee_uan_number')->nullable();
            $table->date('employee_pf_joining_date')->nullable();
            $table->string('eps_membership')->nullable();
            $table->string('abry_eligibility')->nullable();
            $table->string('esi_eligible')->nullable();
            $table->string('employee_esi_number')->nullable();
            $table->string('pt_establishment_id')->nullable();
            $table->string('lwf_eligible')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->default(1);
            $table->unsignedBigInteger('updated_by')->nullable()->default(1);
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
        });

        // 5. CREATE EMPLOYMENT_HISTORY TABLE
        if (!Schema::hasTable('employment_history')) {
            Schema::create('employment_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('designation_id')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable();
                $table->unsignedBigInteger('department_id')->nullable();
                $table->unsignedBigInteger('division_id')->nullable();
                $table->date('from_date');
                $table->date('to_date')->nullable()->comment('NULL = currently active');
                $table->unsignedBigInteger('created_by')->nullable()->default(1);
                $table->unsignedBigInteger('updated_by')->nullable()->default(1);
                $table->unsignedBigInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->foreign('designation_id')->references('id')->on('designations')->onDelete('set null');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
                $table->foreign('division_id')->references('id')->on('divisions')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('deleted_by')->references('id')->on('users')->onDelete('set null');
                $table->index(['employee_id', 'to_date']);
                $table->index(['employee_id', 'from_date', 'to_date']);
            });
        }

        // 6. ADD AUDIT FIELDS TO ASSIGNMENT TABLES (repeat for each)
        $assignmentTables = [
            'employee_branch_assignments',
            'employee_department_assignments',
            'employee_location_assignments',
            'employee_vertical_assignments',
            'employee_post_assignments',
        ];

        foreach ($assignmentTables as $assignmentTable) {
            Schema::table($assignmentTable, function (Blueprint $table) use ($assignmentTable) {
                $afterColumn = in_array($assignmentTable, ['employee_branch_assignments', 'employee_department_assignments', 'employee_location_assignments', 'employee_vertical_assignments']) ? 'is_current' : 'remarks';

                if (!Schema::hasColumn($assignmentTable, 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->default(1)->after($afterColumn);
                }
                if (!Schema::hasColumn($assignmentTable, 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->default(1)->after('created_by');
                }
                if (!Schema::hasColumn($assignmentTable, 'deleted_by')) {
                    $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
                }
                if (!Schema::hasColumn($assignmentTable, 'created_at')) {
                    $table->timestamps();
                }
                if (!Schema::hasColumn($assignmentTable, 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // 7. ADD AUDIT FIELDS TO USERS TABLE
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->default(1)->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->default(1)->after('created_by');
            }
            if (!Schema::hasColumn('users', 'deleted_by')) {
                $table->unsignedBigInteger('deleted_by')->nullable()->after('updated_by');
            }
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'reporting_manager_id')) {
                $table->dropForeign(['reporting_manager_id']);
                $table->dropColumn('reporting_manager_id');
            }
        });

        Schema::dropIfExists('employment_history');
        Schema::dropIfExists('employee_benefits');

        // Drop audit fields from assignment tables (simplified)
        $assignmentTables = ['employee_branch_assignments', 'employee_department_assignments', 'employee_location_assignments', 'employee_vertical_assignments', 'employee_post_assignments'];
        foreach ($assignmentTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'created_by')) {
                    $table->dropColumn(['created_by', 'updated_by', 'deleted_by']);
                }
                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
                if (Schema::hasColumn($tableName, 'created_at')) {
                    $table->dropTimestamps();
                }
            });
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'created_by')) {
                $table->dropColumn(['created_by', 'updated_by', 'deleted_by']);
            }
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
