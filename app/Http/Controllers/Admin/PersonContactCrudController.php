<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class PersonContactCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\PersonContact::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person-contact');
        CRUD::setEntityNameStrings('person contact', 'person contacts');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('person_contact.view')) abort(403);
        CRUD::column('person.first_name')->label('Person First Name');
        CRUD::column('type');
        CRUD::column('name');
        CRUD::column('mobile');
        CRUD::column('email');
        CRUD::column('relationship');
        CRUD::column('is_primary')->type('boolean');
        CRUD::column('verified')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('person_contact.create')) abort(403);
        CRUD::setValidation([
            'person_id'    => 'required|exists:persons,id',
            'type'         => 'required|string|max:50',
            'name'         => 'required|string|max:100',
            'mobile'       => 'nullable|string|max:20',
            'email'        => 'nullable|email|max:100',
            'relationship' => 'nullable|string|max:50',
            'note'         => 'nullable|string',
            'is_primary'   => 'boolean',
            'verified'     => 'boolean',
        ]);

        CRUD::field([
            'name'       => 'person_id',
            'label'      => 'Person',
            'type'       => 'select',
            'entity'     => 'person',
            'attribute'  => 'first_name', // Using 'first_name' since 'name' is not defined
            'model'      => \App\Models\Core\Person::class,
            'attributes' => ['required' => 'required'],
        ]);

        CRUD::field([
            'name'    => 'type',
            'label'   => 'Contact Type',
            'type'    => 'select_from_array',
            'options' => ['phone' => 'Phone', 'email' => 'Email', 'mobile' => 'Mobile'],
            'attributes' => ['required' => 'required'],
        ]);

        CRUD::field([
            'name'       => 'name',
            'label'      => 'Name',
            'type'       => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        CRUD::field([
            'name'  => 'mobile',
            'label' => 'Mobile',
            'type'  => 'text',
        ]);

        CRUD::field([
            'name'  => 'email',
            'label' => 'Email',
            'type'  => 'email',
        ]);

        CRUD::field([
            'name'  => 'relationship',
            'label' => 'Relationship',
            'type'  => 'text',
        ]);

        CRUD::field([
            'name'  => 'note',
            'label' => 'Note',
            'type'  => 'textarea',
        ]);

        CRUD::field([
            'name'    => 'is_primary',
            'label'   => 'Is Primary',
            'type'    => 'boolean',
        ]);

        CRUD::field([
            'name'    => 'verified',
            'label'   => 'Verified',
            'type'    => 'boolean',
            'default' => false,
        ]);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('person_contact.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
