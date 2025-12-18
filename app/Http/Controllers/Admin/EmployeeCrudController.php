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

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('employee.view')) abort(403);
        CRUD::column('code');
        CRUD::column('person.name')->label('Name');
        CRUD::column('designation.name')->label('Designation');
        CRUD::column('date_of_joining');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('employee.create')) abort(403);
        CRUD::field('code');
        CRUD::field('person_id')->type('select2')->entity('person')->attribute('name');
        CRUD::field('designation_id')->type('select2')->entity('designation')->attribute('name');
        CRUD::field('date_of_joining')->type('date');
        CRUD::field('date_of_exit')->type('date')->hint('Leave blank if employed');
        CRUD::field('is_active')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('employee.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
