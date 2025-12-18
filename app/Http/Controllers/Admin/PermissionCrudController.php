<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class PermissionCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Permission::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/permission');
        CRUD::setEntityNameStrings('permission', 'permissions');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('permission.view')) abort(403);
        CRUD::column('name');
        CRUD::column('guard_name');
        CRUD::column('description')->limit(50);
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('permission.create')) abort(403);
        CRUD::field('name')->required()->unique();
        CRUD::field('guard_name')->type('select_from_array')->options(['web' => 'Web', 'api' => 'API'])->default('web');
        CRUD::field('description')->type('textarea');
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('permission.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
