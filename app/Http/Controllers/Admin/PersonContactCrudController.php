<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class PersonContactCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\PersonContact::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person-contact');
        CRUD::setEntityNameStrings('contact', 'contacts');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('person_contact.view')) abort(403);
        CRUD::column('person.name')->label('Person');
        CRUD::column('contact_type');
        CRUD::column('contact_value');
        CRUD::column('is_primary')->type('boolean');
        CRUD::column('verified')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('person_contact.create')) abort(403);
        CRUD::field('person_id')->type('select2')->entity('person')->attribute('name');
        CRUD::field('contact_type')->type('select_from_array')->options(['phone' => 'Phone', 'email' => 'Email', 'mobile' => 'Mobile'])->required();
        CRUD::field('contact_value')->required();
        CRUD::field('is_primary')->type('boolean');
        CRUD::field('verified')->type('boolean')->default(false);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('person_contact.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
