<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use App\Models\Core\Branch;


class DepartmentCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Department::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/department');
        CRUD::setEntityNameStrings('department', 'departments');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('department.view')) abort(403);
        CRUD::column('code');
        CRUD::column('name');
        CRUD::column('branch.name')->label('Branch');
        CRUD::column('is_active')->type('boolean');
    }

    // protected function setupCreateOperation()
    // {
    //     //if (!backpack_user()->can('department.create')) abort(403);
    //     CRUD::field('code');
    //     CRUD::field('name')->required();
    //     CRUD::field('branch_id')->type('select2')->entity('branch')->attribute('name');
    //     CRUD::field('description')->type('textarea');
    //     CRUD::field('is_active')->type('boolean')->default(true);
    // }
    protected function setupCreateOperation()
    {
        CRUD::field('code');

        CRUD::field([
            'name'     => 'name',
            'type'     => 'text',
            'required' => true,
        ]);

        CRUD::field([
            'name'      => 'branch_id',
            'label'     => 'Branch',
            'type'      => 'select',   // ✅ FREE + SAFE
            'entity'    => 'branch',
            'model'     => Branch::class,
            'attribute' => 'name',
        ]);

        CRUD::field('description')->type('textarea');

        CRUD::field([
            'name'    => 'is_active',
            'type'    => 'boolean',
            'default' => true,
        ]);
    }


    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('department.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
