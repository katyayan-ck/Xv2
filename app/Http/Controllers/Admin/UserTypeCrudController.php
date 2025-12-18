<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class UserTypeCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\UserType::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user-type');
        CRUD::setEntityNameStrings('user type', 'user types');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('user_type.view')) abort(403);
        CRUD::column('code');
        CRUD::column('name');
        CRUD::column('description')->limit(50);
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('user_type.create')) abort(403);
        CRUD::field('code')->required();
        CRUD::field('name')->required();
        CRUD::field('description')->type('textarea');
        CRUD::field('is_active')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('user_type.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
