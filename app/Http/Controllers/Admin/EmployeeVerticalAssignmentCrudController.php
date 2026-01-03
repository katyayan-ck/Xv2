<?php

namespace App\Http\Controllers\Admin;

use App\Models\Core\Employee;
use App\Models\Core\Vertical;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class EmployeeVerticalAssignmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\EmployeeVerticalAssignment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/employee-vertical-assignment');
        CRUD::setEntityNameStrings('vertical assignment', 'vertical assignments');
    }

    protected function setupListOperation()
    {
        // Employee Full Name (from Person)
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

        // Vertical Name
        CRUD::addColumn([
            'name'      => 'vertical_id',
            'label'     => 'Vertical',
            'type'      => 'select',
            'entity'    => 'vertical',
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

        // Only is_current exists — show that
        CRUD::column('is_current')
            ->label('Is Current?')
            ->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'employee_id' => 'required|exists:employees,id',
            'vertical_id' => 'required|exists:verticals,id',
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
            ->hint('Search by code or name');

        // Vertical
        CRUD::field('vertical_id')
            ->type('select')
            ->label('Vertical')
            ->entity('vertical')
            ->attribute('name');

        // Dates
        CRUD::field('from_date')
            ->type('date')
            ->label('From Date');

        CRUD::field('to_date')
            ->type('date')
            ->label('To Date')
            ->hint('Leave blank if ongoing');

        // Only is_current exists — show checkbox for it
        CRUD::field('is_current')
            ->type('checkbox')
            ->label('Is Current Assignment?')
            ->default(true);

        // Removed is_primary — it does NOT exist in the table
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
