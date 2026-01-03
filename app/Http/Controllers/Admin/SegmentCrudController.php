<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class SegmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Segment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/segment');
        CRUD::setEntityNameStrings('segment', 'segments');
    }

    protected function setupListOperation()
    {
        // Brand का नाम दिखाने के लिए
        CRUD::column('brand.name')->label('Brand');

        CRUD::column('name');
        CRUD::column('code');
        CRUD::column('description')->limit(100);
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'brand_id'    => 'required|exists:brands,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|size:5|unique:segments,code,NULL,id,brand_id,' . request()->input('brand_id'),
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        // Brand select field — सबसे जरूरी!
        CRUD::field([
            'name'         => 'brand_id',
            'label'        => 'Brand',
            'type'         => 'select',
            'entity'       => 'brand',
            'attribute'    => 'name',
            'model'        => \App\Models\Core\Brand::class,
            'options'      => (function ($query) {
                return $query->orderBy('name', 'ASC')->get();
            }),
        ]);

        CRUD::field([
            'name'       => 'name',
            'label'      => 'Segment Name',
            'type'       => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        CRUD::field([
            'name'       => 'code',
            'label'      => 'Code (5 characters)',
            'type'       => 'text',
            'attributes' => ['maxlength' => 5, 'style' => 'text-transform:uppercase;'],
            'hint'       => 'e.g. HATCH, SUVXX, SEDAN, MPVXX',
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

        // Update के लिए code unique rule adjust (same brand_id में unique)
        $entryId = $this->crud->getCurrentEntryId();
        $brandId = $this->crud->getCurrentEntry()?->brand_id ?? request()->input('brand_id');

        CRUD::setValidation([
            'brand_id'    => 'required|exists:brands,id',
            'name'        => 'required|string|max:255',
            'code'        => "required|string|size:5|unique:segments,code,{$entryId},id,brand_id,{$brandId}",
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);
    }
}
