<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class VerticalCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Vertical::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/vertical');
        CRUD::setEntityNameStrings('vertical', 'verticals');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('vertical.view')) abort(403);
        CRUD::column('code');
        CRUD::column('name');
        CRUD::column('description')->limit(50);
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('vertical.create')) abort(403);
        CRUD::setValidation([
            'code' => 'required|string|max:5|unique:verticals,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        CRUD::field([
            'name'  => 'code',
            'label' => 'Code',
            'type'  => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        CRUD::field([
            'name'  => 'name',
            'label' => 'Name',
            'type'  => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        CRUD::field([
            'name'  => 'description',
            'label' => 'Description',
            'type'  => 'textarea',
        ]);

        CRUD::field([
            'name'    => 'is_active',
            'label'   => 'Active',
            'type'    => 'boolean',
            'default' => true,
        ]);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('vertical.edit')) abort(403);
        $this->setupCreateOperation();

        CRUD::setValidation([
            'code' => 'required|string|max:5|unique:verticals,code,' . $this->crud->getCurrentEntryId(),
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
    }
}

