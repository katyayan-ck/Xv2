<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class VehicleModelCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\VehicleModel::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/vehicle-model');
        CRUD::setEntityNameStrings('vehicle model', 'vehicle models');
    }

    protected function setupListOperation()
    {
        CRUD::column('brand.name')->label('Brand');
        CRUD::column('segment.name')->label('Segment');
        CRUD::column('subSegment.name')->label('Sub Segment'); // Added
        CRUD::column('name');
        CRUD::column('oem_code'); // Added
        CRUD::column('custom_name');
        CRUD::column('description')->limit(100);
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'brand_id'      => 'required|exists:brands,id',
            'segment_id'    => 'required|exists:segments,id',
            'sub_segment_id' => 'nullable|exists:sub_segments,id',
            'name'          => 'required|string|max:255',
            'custom_name'   => 'nullable|string|max:255',
            'oem_code'      => 'nullable|string|max:255|unique:vehicle_models,oem_code',
            'description'   => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        // Brand select
        CRUD::field([
            'name'       => 'brand_id',
            'label'      => 'Brand',
            'type'       => 'select',
            'entity'     => 'brand',
            'attribute'  => 'name',
            'model'      => \App\Models\Core\Brand::class,
        ]);

        // Segment select
        CRUD::field([
            'name'       => 'segment_id',
            'label'      => 'Segment',
            'type'       => 'select',
            'entity'     => 'segment',
            'attribute'  => 'name',
            'model'      => \App\Models\Core\Segment::class,
        ]);

        // Sub Segment select (optional)
        CRUD::field([
            'name'       => 'sub_segment_id',
            'label'      => 'Sub Segment (Optional)',
            'type'       => 'select',
            'entity'     => 'subSegment',
            'attribute'  => 'name',
            'model'      => \App\Models\Core\SubSegment::class,
        ]);

        CRUD::field([
            'name'       => 'name',
            'label'      => 'Model Name',
            'type'       => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        CRUD::field([
            'name'       => 'custom_name',
            'label'      => 'Custom Name (Optional)',
            'type'       => 'text',
        ]);

        CRUD::field([
            'name'       => 'oem_code',
            'label'      => 'OEM Code',
            'type'       => 'text',
            'attributes' => ['style' => 'text-transform:uppercase;'],
            'hint'       => 'e.g. BREZ1, EXTER1, NEXON (auto uppercase)',
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

        // Update validation
        $entryId = $this->crud->getCurrentEntryId();
        CRUD::setValidation([
            'brand_id'      => 'required|exists:brands,id',
            'segment_id'    => 'required|exists:segments,id',
            'sub_segment_id' => 'nullable|exists:sub_segments,id',
            'name'          => 'required|string|max:255',
            'custom_name'   => 'nullable|string|max:255',
            'oem_code'      => 'nullable|string|max:255|unique:vehicle_models,oem_code,' . $entryId,
            'description'   => 'nullable|string',
            'is_active'     => 'boolean',
        ]);
    }
}
