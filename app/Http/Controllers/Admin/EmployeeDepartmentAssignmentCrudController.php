<?php

namespace App\Http\Controllers\Admin;

use App\Models\Core\Employee;
use App\Models\Core\Department;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class EmployeeDepartmentAssignmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\EmployeeDepartmentAssignment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/employee-department-assignment');
        CRUD::setEntityNameStrings('department assignment', 'department assignments');
    }

    protected function setupListOperation()
    {
        // Employee Name (full name from Person)
        CRUD::addColumn([
            'name'       => 'employee_id',
            'label'      => 'Employee',
            'type'       => 'closure',
            'function'   => function ($entry) {
                if ($entry->employee && $entry->employee->person) {
                    return trim($entry->employee->person->first_name . ' ' . $entry->employee->person->last_name);
                }
                return '-';
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('employee.person', function ($q) use ($searchTerm) {
                    $q->where('first_name', 'like', "%{$searchTerm}%")
                        ->orWhere('last_name', 'like', "%{$searchTerm}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"]);
                });
            },
        ]);

        // Department
        CRUD::addColumn([
            'name'      => 'department_id',
            'label'     => 'Department',
            'type'      => 'select',
            'entity'    => 'department',
            'attribute' => 'name',
        ]);

        // Dates
        CRUD::column('from_date')
            ->label('From Date')
            ->type('date')
            ->format('DD/MM/YYYY');

        CRUD::column('to_date')
            ->label('To Date')
            ->type('date')
            ->format('DD/MM/YYYY');

        // Flags
        // CRUD::column('is_primary')->type('boolean');
        CRUD::column('is_current')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'employee_id'   => 'required|exists:employees,id',
            'department_id' => 'required|exists:departments,id',
            'from_date'     => 'required|date',
            'to_date'       => 'nullable|date|after_or_equal:from_date',
            'is_primary'    => 'boolean',
            'is_current'    => 'boolean',
        ]);

        // Employee - Show "EMP005 - Keshav Kumar" using options closure
        CRUD::field('employee_id')
            ->type('select')  // searchable dropdown
            ->label('Employee')
            ->entity('employee')
            ->model(\App\Models\Core\Employee::class)
            ->options(function ($query) {
                return $query->with('person')  // eager load person
                    ->orderBy('code')
                    ->get()
                    ->map(function ($employee) {
                        $name = $employee->person
                            ? trim($employee->person->first_name . ' ' . $employee->person->last_name)
                            : 'No Person';
                        $employee->name = $employee->code . ' - ' . $name;  // temporary attribute
                        return $employee;
                    });
            })
            ->attribute('name')  // now safe: uses the temporary 'name' we added
            ->hint('Search by code or name');

        // Department
        CRUD::field('department_id')
            ->type('select')
            ->label('Department')
            ->entity('department')
            ->attribute('name');

        // Dates
        CRUD::field('from_date')
            ->type('date')
            ->label('From Date');

        CRUD::field('to_date')
            ->type('date')
            ->label('To Date')
            ->hint('Leave blank if ongoing');

        // Checkboxes
        // CRUD::field('is_primary')
        //     ->type('checkbox')
        //     ->label('Is Primary Department?');

        CRUD::field('is_current')
            ->type('checkbox')
            ->label('Is Current?')
            ->default(true);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
