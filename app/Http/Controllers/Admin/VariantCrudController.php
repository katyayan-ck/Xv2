<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class VariantCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Variant::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/variant');
        CRUD::setEntityNameStrings('variant', 'variants');
    }

    protected function setupListOperation()
    {
        CRUD::column('vehicleModel.brand.name')->label('Brand');
        CRUD::column('vehicleModel.segment.name')->label('Segment');
        CRUD::column('vehicleModel.subSegment.name')->label('Sub Segment');
        CRUD::column('vehicleModel.name')->label('Model');
        CRUD::column('name')->label('Variant Name');
        CRUD::column('custom_name');
        CRUD::column('oem_code');
        CRUD::column('seating_capacity');
        CRUD::column('wheels');
        CRUD::column('gvw');
        CRUD::column('cc_capacity');
        CRUD::column('is_csd')->type('boolean');
        CRUD::column('csd_index');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'brand_id'         => 'required|exists:brands,id',
            'segment_id'       => 'required|exists:segments,id',
            'vehicle_model_id' => 'required|exists:vehicle_models,id',
            'name'             => 'required|string|max:255',
            'custom_name'      => 'nullable|string|max:255',
            'oem_code'         => 'nullable|string|max:255|unique:variants,oem_code',
            'description'      => 'nullable|string',
            'permit_id'        => 'nullable|exists:keyvalues,id',
            'fuel_type_id'     => 'nullable|exists:keyvalues,id',
            'seating_capacity' => 'nullable|integer|min:1',
            'wheels'           => 'nullable|integer|min:2',
            'gvw'              => 'nullable|integer',
            'cc_capacity'      => 'nullable|string|max:255',
            'body_type_id'     => 'nullable|exists:keyvalues,id',
            'body_make_id'     => 'nullable|exists:keyvalues,id',
            'is_csd'           => 'boolean',
            'csd_index'        => 'nullable|string|max:255',
            'status_id'        => 'nullable|exists:keyvalues,id',
            'is_active'        => 'boolean',
        ]);

        // Brand
        CRUD::field([
            'name'       => 'brand_id',
            'label'      => 'Brand',
            'type'       => 'select',
            'entity'     => 'brand',
            'attribute'  => 'name',
            'model'      => \App\Models\Core\Brand::class,
        ]);

        // Segment
        CRUD::field([
            'name'       => 'segment_id',
            'label'      => 'Segment',
            'type'       => 'select',
            'entity'     => 'segment',
            'attribute'  => 'name',
            'model'      => \App\Models\Core\Segment::class,
        ]);

        // Sub Segment (optional)
        CRUD::field([
            'name'       => 'sub_segment_id',
            'label'      => 'Sub Segment (Optional)',
            'type'       => 'select',
            'entity'     => 'subSegment',
            'attribute'  => 'name',
            'model'      => \App\Models\Core\SubSegment::class,
            'allows_null' => true,
        ]);

        // Vehicle Model
        CRUD::field([
            'name'       => 'vehicle_model_id',
            'label'      => 'Vehicle Model',
            'type'       => 'select',
            'entity'     => 'vehicleModel',
            'attribute'  => 'name',
            'model'      => \App\Models\Core\VehicleModel::class,
        ]);

        // Variant Name
        CRUD::field([
            'name'       => 'name',
            'label'      => 'Variant Name',
            'type'       => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        // Custom Name
        CRUD::field([
            'name'  => 'custom_name',
            'label' => 'Custom Name (Optional)',
            'type'  => 'text',
        ]);

        // OEM Code
        CRUD::field([
            'name'       => 'oem_code',
            'label'      => 'OEM Code',
            'type'       => 'text',
            'attributes' => ['style' => 'text-transform:uppercase;'],
        ]);

        // Description
        CRUD::field([
            'name'  => 'description',
            'label' => 'Description',
            'type'  => 'textarea',
        ]);

        // Permit
        CRUD::field([
            'name'    => 'permit_id',
            'label'   => 'Permit',
            'type'    => 'select_from_array',
            'options' => [null => '- Select -', 1 => 'Private', 2 => 'Commercial', 3 => 'Tourist', 4 => 'Taxi'],
            'allows_null' => true,
        ]);

        // Fuel Type
        CRUD::field([
            'name'    => 'fuel_type_id',
            'label'   => 'Fuel Type',
            'type'    => 'select_from_array',
            'options' => [null => '- Select -', 1 => 'Petrol', 2 => 'Diesel', 3 => 'CNG', 4 => 'LPG', 5 => 'Electric', 6 => 'Hybrid'],
            'allows_null' => true,
        ]);

        // Seating Capacity
        CRUD::field([
            'name'    => 'seating_capacity',
            'label'   => 'Seating Capacity',
            'type'    => 'number',
            'attributes' => ['min' => 1],
        ]);

        // Wheels
        CRUD::field([
            'name'    => 'wheels',
            'label'   => 'Wheels',
            'type'    => 'number',
            'default' => 4,
        ]);

        // GVW
        CRUD::field([
            'name'  => 'gvw',
            'label' => 'GVW (kg)',
            'type'  => 'number',
        ]);

        // Engine CC
        CRUD::field([
            'name'  => 'cc_capacity',
            'label' => 'Engine CC',
            'type'  => 'text',
        ]);

        // Body Type
        CRUD::field([
            'name'    => 'body_type_id',
            'label'   => 'Body Type',
            'type'    => 'select_from_array',
            'options' => [null => '- Select -', 1 => 'SUV', 2 => 'Sedan', 3 => 'Hatchback', 4 => 'MPV', 5 => 'Pickup'],
            'allows_null' => true,
        ]);

        // Body Make
        CRUD::field([
            'name'    => 'body_make_id',
            'label'   => 'Body Make',
            'type'    => 'select_from_array',
            'options' => [null => '- Select -', 1 => 'OEM Factory', 2 => 'Aftermarket'],
            'allows_null' => true,
        ]);

        // CSD Available
        CRUD::field([
            'name'    => 'is_csd',
            'label'   => 'CSD Available',
            'type'    => 'boolean',
        ]);

        // CSD Index
        CRUD::field([
            'name'  => 'csd_index',
            'label' => 'CSD Index',
            'type'  => 'text',
        ]);

        // Status
        CRUD::field([
            'name'    => 'status_id',
            'label'   => 'Status',
            'type'    => 'select_from_array',
            'options' => [null => '- Select -', 1 => 'Available', 2 => 'Upcoming', 3 => 'Discontinued'],
            'allows_null' => true,
        ]);

        // Active
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
    }
}
