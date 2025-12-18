<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class GarageCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Garage::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/garage');
        CRUD::setEntityNameStrings('garage', 'garages');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('garage.view')) abort(403);
        CRUD::column('code');
        CRUD::column('name');
        CRUD::column('location')->limit(50);
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('garage.create')) abort(403);
        CRUD::field('code');
        CRUD::field('name')->required();
        CRUD::field('location')->type('textarea');
        CRUD::field('manager_id')->type('select2')->entity('employee')->attribute('name')->hint('Optional');
        CRUD::field('is_active')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('garage.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
