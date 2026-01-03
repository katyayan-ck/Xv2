<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use App\Models\Core\Department;

class DivisionCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Division::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/division');
        CRUD::setEntityNameStrings('division', 'divisions');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('division.view')) abort(403);
        CRUD::column('code');
        CRUD::column('name');
        CRUD::column('department.name')->label('Department');
        CRUD::column('is_active')->type('boolean');
    }

    // protected function setupCreateOperation()
    // {
    //     //if (!backpack_user()->can('division.create')) abort(403);
    //     CRUD::field('code');
    //     CRUD::field('name')->required();
    //     CRUD::field('description')->type('textarea');
    //     CRUD::field('is_active')->type('boolean')->default(true);
    // }

    protected function setupCreateOperation()
    {
        CRUD::field([
            'name'  => 'code',
            'type'  => 'text',
            'label' => 'Code',
        ]);

        CRUD::field([
            'name'     => 'name',
            'type'     => 'text',
            'required' => true,
        ]);

        // 🔥 THIS IS COMPULSORY
        CRUD::field([
            'name'      => 'department_id',
            'label'     => 'Department',
            'type'      => 'select',      // FREE & SAFE
            'entity'    => 'department',
            'model'     => Department::class,
            'attribute' => 'name',
        ]);

        CRUD::field([
            'name' => 'description',
            'type' => 'textarea',
        ]);

        CRUD::field([
            'name'    => 'is_active',
            'type'    => 'boolean',
            'default' => true,
        ]);
    }



    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('division.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
