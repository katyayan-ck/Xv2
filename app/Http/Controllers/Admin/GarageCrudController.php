<?php

namespace App\Http\Controllers\Admin;

use App\Models\Core\Garage;
use App\Models\Core\Person; // if you want person relationship
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class GarageCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Garage::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/garage');
        CRUD::setEntityNameStrings('garage', 'garages');
    }

    protected function setupListOperation()
    {
        CRUD::column('name');
        CRUD::column('address')->limit(60);
        CRUD::column('city');
        CRUD::column('state');
        CRUD::column('mobile');

        CRUD::addColumn([
            'name'      => 'person_id',
            'label'     => 'Owner / Person',
            'type'      => 'select',
            'entity'    => 'person',
            'attribute' => 'display_name', // adjust to whatever accessor you have on Person model
        ]);

        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        // Validation (optional but recommended)
        CRUD::setValidation([
            'name'         => 'required|string|max:255',
            'address'      => 'nullable|string|max:255',
            'city'         => 'nullable|string|max:255',
            'state'        => 'nullable|string|max:255',
            'pincode'      => 'nullable|string|max:20',
            'mobile'       => 'nullable|string|max:20',
            'is_active'    => 'boolean',
        ]);

        // Correct fluent syntax
        CRUD::field('name')
            ->label('Garage Name')
            ->type('text')
            ->attributes(['required' => 'required']);

        CRUD::field('type')->type('text')->label('Type (e.g., Workshop, Showroom)');

        CRUD::field('address')->type('textarea')->label('Address');

        CRUD::field('city')->type('text');
        CRUD::field('state')->type('text');
        CRUD::field('pincode')->type('text')->label('Pincode');

        CRUD::field('latitude')->type('number')->attributes(['step' => 'any']);
        CRUD::field('longitude')->type('number')->attributes(['step' => 'any']);

        CRUD::field('contact_person')->type('text')->label('Contact Person');
        CRUD::field('mobile')->type('text');

        // Person relationship (belongsTo)
        CRUD::field('person_id')
            ->type('select')
            ->label('Associated Person / Owner')
            ->entity('person')
            ->model(Person::class)
            ->attribute('display_name') // or 'name', 'full_name', etc.
            ->hint('Optional');

        CRUD::field('is_active')
            ->type('checkbox')
            ->label('Active')
            ->default(true);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
