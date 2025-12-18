<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class VehicleModelCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\VehicleModel::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/vehicle-model');
        CRUD::setEntityNameStrings('vehicle model', 'vehicle models');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('vehicle_model.view')) abort(403);
        CRUD::column('brand.name')->label('Brand');
        CRUD::column('segment.name')->label('Segment');
        CRUD::column('name');
        CRUD::column('body_type');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('vehicle_model.create')) abort(403);
        CRUD::field('brand_id')->type('select2')->entity('brand')->attribute('name');
        CRUD::field('segment_id')->type('select2')->entity('segment')->attribute('name');
        CRUD::field('name')->required();
        CRUD::field('body_type')->type('select_from_array')->options(['suv' => 'SUV', 'sedan' => 'Sedan', 'hatchback' => 'Hatchback', 'mpv' => 'MPV', 'truck' => 'Truck']);
        CRUD::field('description')->type('textarea');
        CRUD::field('is_active')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('vehicle_model.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
