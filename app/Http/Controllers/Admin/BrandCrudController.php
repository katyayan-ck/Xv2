<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class BrandCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Brand::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/brand');
        CRUD::setEntityNameStrings('brand', 'brands');
    }

    protected function setupListOperation()
    {
        CRUD::column('name');
        CRUD::column('code');
        CRUD::column('description')->limit(100);
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|size:5|unique:brands,code',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        CRUD::field([
            'name'       => 'name',
            'label'      => 'Brand Name',
            'type'       => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        CRUD::field([
            'name'       => 'code',
            'label'      => 'Code (5 characters)',
            'type'       => 'text',
            'attributes' => ['maxlength' => 5, 'style' => 'text-transform:uppercase;'],
            'hint'       => 'e.g. MARUT, TATAM, HYUND, MGMOT',
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
        $this->setupCreateOperation();

        CRUD::setValidation([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|size:5|unique:brands,code,' . $this->crud->getCurrentEntryId(),
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);
    }
}
