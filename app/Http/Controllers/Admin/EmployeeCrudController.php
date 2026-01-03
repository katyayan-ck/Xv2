<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class EmployeeCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Employee::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/employee');
        CRUD::setEntityNameStrings('employee', 'employees');
    }

    // protected function setupListOperation()
    // {
    //     //if (!backpack_user()->can('employee.view')) abort(403);
    //     CRUD::column('code');
    //     CRUD::column('person.name')->label('Name');
    //     CRUD::column('designation.name')->label('Designation');
    //     CRUD::column('date_of_joining');
    //     CRUD::column('is_active')->type('boolean');
    // }
    protected function setupListOperation()
    {
        // Code
        CRUD::column('code')->label('Code');

        // Person Name – without relying on display_name accessor
        CRUD::addColumn([
            'name'       => 'person_id',
            'label'      => 'Name',
            'type'       => 'closure',                 // custom display
            'function'   => function ($entry) {
                // Safely get first_name and last_name from related person
                if ($entry->person) {
                    return trim($entry->person->first_name . ' ' . $entry->person->last_name);
                }
                return '-';
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('person', function ($q) use ($searchTerm) {
                    $q->where('first_name', 'like', "%{$searchTerm}%")
                        ->orWhere('last_name', 'like', "%{$searchTerm}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"]);
                });
            },
        ]);

        // Designation
        CRUD::addColumn([
            'name'      => 'designation_id',
            'label'     => 'Designation',
            'type'      => 'select',
            'entity'    => 'designation',
            'attribute' => 'name',
        ]);

        // Correct joining date column name + nice format
        CRUD::column('joining_date')
            ->label('Date of Joining')
            ->type('date')
            ->format('DD/MM/YYYY');  // shows as 03/01/2026 etc.

        // Optional: Resignation date
        CRUD::addColumn([
            'name'     => 'resignation_date',
            'label'    => 'Date of Exit',
            'type'     => 'closure',
            'function' => function ($entry) {
                return $entry->resignation_date ? $entry->resignation_date->format('d/m/Y') : '-';
            }
        ]);

        // Active status
        CRUD::column('is_active')
            ->label('Active')
            ->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'code'                  => 'required|unique:employees,code,' . ($this->crud->getCurrentEntryId() ?? ''),
            'person_id'             => 'required|exists:persons,id',
            'designation_id'        => 'required|exists:designations,id',
            'primary_branch_id'     => 'required|exists:branches,id',
            'primary_department_id' => 'required|exists:departments,id',
            'joining_date'          => 'required|date',
            'resignation_date'      => 'nullable|date|after_or_equal:joining_date',
            'employment_type'       => 'required|in:permanent,contract,temporary,probation',
            'is_active'             => 'boolean',
        ]);

        CRUD::field('code')->type('text')->hint('e.g., EMP001');

        CRUD::field('person_id')
            ->type('select')
            ->label('Person')
            ->entity('person')
            ->model(\App\Models\Core\Person::class)
            ->attribute('display_name')
            ->hint('Select the person this employee record belongs to');

        CRUD::field('designation_id')
            ->type('select')
            ->label('Designation')
            ->entity('designation')
            ->attribute('name');

        CRUD::field('primary_branch_id')
            ->type('select')
            ->label('Primary Branch')
            ->entity('primaryBranch')
            ->attribute('name');

        CRUD::field('primary_department_id')
            ->type('select')
            ->label('Primary Department')
            ->entity('primaryDepartment')
            ->attribute('name');

        CRUD::field('joining_date')
            ->type('date')
            ->label('Joining Date');

        CRUD::field('resignation_date')
            ->type('date')
            ->label('Date of Exit')
            ->hint('Leave blank if currently employed');

        CRUD::field('employment_type')
            ->type('select_from_array')
            ->options([
                'permanent'  => 'Permanent',
                'contract'   => 'Contract',
                'temporary'  => 'Temporary',
                'probation'  => 'Probation',
            ])
            ->default('permanent');

        CRUD::field('is_active')
            ->type('checkbox')
            ->label('Active')
            ->default(true);
    }
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
