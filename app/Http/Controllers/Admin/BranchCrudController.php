<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Requests\CrudRequest;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use App\Http\Controllers\Admin\Traits\ScopedCrud;

class BranchCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;
    use ScopedCrud;

    protected function getScopeType(): string
    {
        return 'branch'; // or 'location', etc.
    }

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Branch::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/branch');
        CRUD::setEntityNameStrings('branch', 'branches');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('branch.view')) abort(403);
        CRUD::column('code');
        CRUD::column('name');
        CRUD::column('short_name');
        CRUD::column('phone');
        CRUD::column('email');
        CRUD::column('city');
        CRUD::column('state');
        CRUD::column('is_head_office')->type('boolean');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('branch.create')) abort(403);
        CRUD::field('code');
        CRUD::field('name');
        CRUD::field('short_name');
        CRUD::field('description')->type('textarea');
        CRUD::field('phone');
        CRUD::field('email');
        CRUD::field('address')->type('textarea');
        CRUD::field('city');
        CRUD::field('state');
        CRUD::field('pincode');
        CRUD::field('country');
        CRUD::field('latitude');
        CRUD::field('longitude');
        CRUD::field('is_head_office')->type('checkbox');
        CRUD::field('is_active')->type('checkbox');
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('branch.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
