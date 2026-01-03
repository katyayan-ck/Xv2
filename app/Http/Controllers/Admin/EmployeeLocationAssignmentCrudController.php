<?php

namespace App\Http\Controllers\Admin;

use App\Models\Core\Employee;
use App\Models\Core\Location;
use App\Models\Core\Branch;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class EmployeeLocationAssignmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\EmployeeLocationAssignment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/employee-location-assignment');
        CRUD::setEntityNameStrings('location assignment', 'location assignments');
    }

    protected function setupListOperation()
    {
        // Employee Full Name
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

        // Location
        CRUD::addColumn([
            'name'      => 'location_id',
            'label'     => 'Location',
            'type'      => 'select',
            'entity'    => 'location',
            'attribute' => 'name',
        ]);

        // Branch (optional - shows '-' if none)
        CRUD::addColumn([
            'name'      => 'branch_id',
            'label'     => 'Branch',
            'type'      => 'select',
            'entity'    => 'branch',
            'attribute' => 'name',
            'allows_null' => true,
            'default' => '-',
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

        // Current status
        CRUD::column('is_current')
            ->label('Is Current?')
            ->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'employee_id' => 'required|exists:employees,id',
            'location_id' => 'required|exists:locations,id',
            'branch_id'   => 'nullable|exists:branches,id',     // Important: nullable
            'from_date'   => 'required|date',
            'to_date'     => 'nullable|date|after_or_equal:from_date',
            'is_current'  => 'boolean',
        ]);

        // Employee - Shows "EMP005 - Keshav Kumar" and searchable
        CRUD::field('employee_id')
            ->type('select')
            ->label('Employee')
            ->entity('employee')
            ->model(Employee::class)
            ->options(function ($query) {
                return $query->with('person')
                    ->orderBy('code')
                    ->get()
                    ->map(function ($employee) {
                        $name = $employee->person
                            ? trim($employee->person->first_name . ' ' . $employee->person->last_name)
                            : 'No Person';
                        $employee->name = $employee->code . ' - ' . $name;
                        return $employee;
                    });
            })
            ->attribute('name')
            ->hint('Search by employee code or name');

        // Location
        CRUD::field('location_id')
            ->type('select')
            ->label('Location')
            ->entity('location')
            ->attribute('name');

        // Branch - Optional
        CRUD::field('branch_id')
            ->type('select')
            ->label('Branch (Optional)')
            ->entity('branch')
            ->attribute('name')
            ->allows_null(true)
            ->placeholder('— No branch assigned —');

        // Dates
        CRUD::field('from_date')
            ->type('date')
            ->label('From Date');

        CRUD::field('to_date')
            ->type('date')
            ->label('To Date')
            ->hint('Leave blank if assignment is ongoing');

        // Current flag
        CRUD::field('is_current')
            ->type('checkbox')
            ->label('Is Current Assignment?')
            ->default(true);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
