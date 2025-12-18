<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class PersonAddressCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\PersonAddress::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person-address');
        CRUD::setEntityNameStrings('address', 'addresses');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('person_address.view')) abort(403);
        CRUD::column('person.name')->label('Person');
        CRUD::column('address_type');
        CRUD::column('address')->limit(50);
        CRUD::column('city');
        CRUD::column('state');
        CRUD::column('is_primary')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('person_address.create')) abort(403);
        CRUD::field('person_id')->type('select2')->entity('person')->attribute('name');
        CRUD::field('address_type')->type('select_from_array')->options(['residential' => 'Residential', 'official' => 'Official', 'other' => 'Other'])->required();
        CRUD::field('address')->type('textarea')->required();
        CRUD::field('city')->required();
        CRUD::field('state')->required();
        CRUD::field('pincode');
        CRUD::field('country');
        CRUD::field('is_primary')->type('boolean');
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('person_address.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
