<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class ColorCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Color::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/color');
        CRUD::setEntityNameStrings('color', 'colors');
    }

    protected function setupListOperation()
    {
        CRUD::column('brand.name')->label('Brand');
        CRUD::column('name');
        CRUD::column('code');
        CRUD::column('hex_code');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'brand_id'   => 'required|exists:brands,id',
            'name'       => 'required|string|max:255',
            'code'       => 'required|string|max:50|unique:colors,code',
            'hex_code'   => 'required|string|max:7',
            'is_active'  => 'boolean',
        ]);

        // Brand - Required
        CRUD::field([
            'name'       => 'brand_id',
            'label'      => 'Brand',
            'type'       => 'select',
            'entity'     => 'brand',
            'attribute'  => 'name',
            'model'      => \App\Models\Core\Brand::class,
            'attributes' => ['required' => 'required'],
        ]);

        // Color Name - Required
        CRUD::field([
            'name'       => 'name',
            'label'      => 'Color Name',
            'type'       => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        // Code - Required (5-10 chars recommended)
        CRUD::field([
            'name'       => 'code',
            'label'      => 'Code',
            'type'       => 'text',
            'attributes' => ['required' => 'required', 'style' => 'text-transform:uppercase;'],
            'hint'       => 'e.g. RED, WHITE, BLUE (auto uppercase)',
        ]);

        // Hex Code - Required
        CRUD::field([
            'name'       => 'hex_code',
            'label'      => 'Hex Code',
            'type'       => 'text',
            'hint'       => 'e.g. #FF0000',
            'attributes' => ['required' => 'required'],
        ]);

        // Active - Default Yes
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

        $entryId = $this->crud->getCurrentEntryId();

        CRUD::setValidation([
            'brand_id'   => 'required|exists:brands,id',
            'name'       => 'required|string|max:255',
            'code'       => "required|string|max:50|unique:colors,code,{$entryId}",
            'hex_code'   => 'required|string|max:7',
            'is_active'  => 'boolean',
        ]);
    }
}
