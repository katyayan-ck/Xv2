<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Requests\PersonRequest;

class PersonCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Person::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person');
        CRUD::setEntityNameStrings('person', 'persons');
    }

    protected function setupListOperation()
    {
        if (!backpack_user()->can('person.view')) abort(403);

        CRUD::column('code');
        CRUD::column('first_name');
        CRUD::column('last_name');
        CRUD::column('email_primary')->label('Email');          // ✅ FIXED
        CRUD::column('mobile_primary')->label('Mobile');        // ✅ FIXED
        CRUD::column('dob')->label('Date of Birth');            // ✅ FIXED
        CRUD::column('gender');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidationRequest(PersonRequest::class);

        CRUD::field('code');
        CRUD::field('first_name')->required();
        CRUD::field('middle_name');
        CRUD::field('last_name')->required();
        CRUD::field('email_primary')->label('Email Primary');   // ✅ FIXED
        CRUD::field('email_secondary')->label('Email Secondary');
        CRUD::field('mobile_primary')->label('Mobile Primary');  // ✅ FIXED
        CRUD::field('mobile_secondary')->label('Mobile Secondary');
        CRUD::field('dob')->type('date')->label('Date of Birth'); // ✅ FIXED
        CRUD::field('gender')->type('select_from_array')->options(['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say']);
        CRUD::field('is_active')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidationRequest(PersonRequest::class);
        $this->setupCreateOperation();
    }
}
