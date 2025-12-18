<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class VariantCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Variant::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/variant');
        CRUD::setEntityNameStrings('variant', 'variants');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('variant.view')) abort(403);
        CRUD::column('vehicle_model.name')->label('Model');
        CRUD::column('name');
        CRUD::column('price');
        CRUD::column('engine_type');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('variant.create')) abort(403);
        CRUD::field('vehicle_model_id')->type('select2')->entity('vehicleModel')->attribute('name');
        CRUD::field('name')->required();
        CRUD::field('price')->type('number');
        CRUD::field('engine_type')->type('select_from_array')->options(['petrol' => 'Petrol', 'diesel' => 'Diesel', 'hybrid' => 'Hybrid', 'electric' => 'Electric']);
        CRUD::field('transmission')->type('select_from_array')->options(['manual' => 'Manual', 'automatic' => 'Automatic']);
        CRUD::field('seating_capacity')->type('number');
        CRUD::field('description')->type('textarea');
        CRUD::field('is_active')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('variant.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
