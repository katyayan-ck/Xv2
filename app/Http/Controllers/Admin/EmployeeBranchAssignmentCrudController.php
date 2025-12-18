<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class EmployeeBranchAssignmentCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\EmployeeBranchAssignment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/employee-branch-assignment');
        CRUD::setEntityNameStrings('assignment', 'assignments');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('employee_branch_assignment.view')) abort(403);
        CRUD::column('employee.name')->label('Employee');
        CRUD::column('branch.name')->label('Branch');
        CRUD::column('from_date');
        CRUD::column('to_date');
        CRUD::column('is_primary')->type('boolean');
        CRUD::column('is_current')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('employee_branch_assignment.create')) abort(403);
        CRUD::field('employee_id')->type('select2')->entity('employee')->attribute('name');
        CRUD::field('branch_id')->type('select2')->entity('branch')->attribute('name');
        CRUD::field('from_date')->type('date');
        CRUD::field('to_date')->type('date')->hint('Leave blank if ongoing');
        CRUD::field('is_primary')->type('boolean');
        CRUD::field('is_current')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('employee_branch_assignment.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
