<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class PostPermissionCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\PostPermission::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/post-permission');
        CRUD::setEntityNameStrings('post permission', 'post permissions');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('post_permission.view')) abort(403);
        CRUD::column('post.name')->label('Post');
        CRUD::column('permission.name')->label('Permission');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('post_permission.create')) abort(403);
        CRUD::field('post_id')->type('select2')->entity('post')->attribute('name');
        CRUD::field('permission_id')->type('select2')->entity('permission')->attribute('name');
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('post_permission.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
