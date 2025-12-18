<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class LocationCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Location::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/location');
        CRUD::setEntityNameStrings('location', 'locations');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('location.view')) abort(403);
        CRUD::column('code');
        CRUD::column('name');
        CRUD::column('branch.name')->label('Branch');
        CRUD::column('city');
        CRUD::column('state');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('location.create')) abort(403);
        CRUD::field('code');
        CRUD::field('name')->required();
        CRUD::field('branch_id')->type('select2')->entity('branch')->attribute('name');
        CRUD::field('description')->type('textarea');
        CRUD::field('address')->type('textarea');
        CRUD::field('city');
        CRUD::field('state');
        CRUD::field('pincode');
        CRUD::field('country');
        CRUD::field('latitude');
        CRUD::field('longitude');
        CRUD::field('is_active')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('location.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
