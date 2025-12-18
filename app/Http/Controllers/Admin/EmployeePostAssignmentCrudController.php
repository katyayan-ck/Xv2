<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class EmployeePostAssignmentCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\EmployeePostAssignment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/employee-post-assignment');
        CRUD::setEntityNameStrings('assignment', 'assignments');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('employee_post_assignment.view')) abort(403);
        CRUD::column('employee.name')->label('Employee');
        CRUD::column('post.name')->label('Post');
        CRUD::column('from_date');
        CRUD::column('is_primary')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('employee_post_assignment.create')) abort(403);
        CRUD::field('employee_id')->type('select2')->entity('employee')->attribute('name');
        CRUD::field('post_id')->type('select2')->entity('post')->attribute('name');
        CRUD::field('from_date')->type('date');
        CRUD::field('to_date')->type('date');
        CRUD::field('is_primary')->type('boolean');
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('employee_post_assignment.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
