<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class PersonAddressCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    /**
     * Configure the CrudPanel object.
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Core\PersonAddress::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person-address');
        CRUD::setEntityNameStrings('person address', 'person addresses');
    }

    /**
     * List operation - show addresses with person name.
     */
    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name'      => 'person_id',
            'label'     => 'Person',
            'type'      => 'select',
            'entity'    => 'person',
            'attribute' => 'full_name',
        ]);

        CRUD::addColumn([
            'name'  => 'type',
            'label' => 'Type',
        ]);

        // 🔥 THIS WAS MISSING / WRONG
        CRUD::addColumn([
            'name'  => 'address_line_1',
            'label' => 'Address Line 1',
        ]);

        CRUD::addColumn([
            'name'  => 'address_line_2',
            'label' => 'Address Line 2',
        ]);

        CRUD::addColumn([
            'name'  => 'city',
            'label' => 'City',
        ]);

        CRUD::addColumn([
            'name'  => 'state',
            'label' => 'State',
        ]);

        CRUD::addColumn([
            'name'  => 'pincode',
            'label' => 'Pincode',
        ]);

        CRUD::addColumn([
            'name'  => 'country',
            'label' => 'Country',
        ]);

        CRUD::addColumn([
            'name'  => 'is_primary',
            'label' => 'Primary',
            'type'  => 'boolean',
        ]);
    }


    /**
     * Create & Update operations - all fields with proper person dropdown.
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'person_id'      => 'required|exists:persons,id',
            'type'           => 'required|in:residential,official,other',
            'address_line_1'  => 'required|string|max:255',
            'address_line_2'  => 'nullable|string|max:255',
            'city'           => 'required|string|max:100',
            'state'          => 'required|string|max:100',
            'pincode'        => 'nullable|string|max:20',
            'country'        => 'nullable|string|max:100',
            'is_primary'     => 'boolean',
            'is_active'      => 'boolean',
        ]);

        // Person dropdown (using first_name + last_name via closure)
        CRUD::field([
            'name'       => 'person_id',
            'label'      => 'Person',
            'type'       => 'select',
            'entity'     => 'person',
            'attribute'  => 'first_name', // fallback, but we'll customize display
            'model'      => \App\Models\Core\Person::class,
            'attributes' => ['required' => 'required'],
            'options'    => function ($query) {
                return $query->get()->map(function ($person) {
                    $person->name = trim($person->first_name . ' ' . $person->last_name);
                    return $person;
                });
            },
        ]);

        // Address Type
        CRUD::field([
            'name'    => 'type',
            'label'   => 'Address Type',
            'type'    => 'select_from_array',
            'options' => ['residential' => 'Residential', 'official' => 'Official', 'other' => 'Other'],
            'attributes' => ['required' => 'required'],
        ]);

        // Address Line 1
        CRUD::field([
            'name'       => 'address_line_1',
            'label'      => 'Address Line 1',
            'type'       => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        // Address Line 2
        CRUD::field([
            'name'  => 'address_line_2',
            'label' => 'Address Line 2',
            'type'  => 'text',
        ]);

        // City
        CRUD::field([
            'name'       => 'city',
            'label'      => 'City',
            'type'       => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        // State
        CRUD::field([
            'name'       => 'state',
            'label'      => 'State',
            'type'       => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        // Pincode
        CRUD::field([
            'name'  => 'pincode',
            'label' => 'Pincode',
            'type'  => 'text',
        ]);

        // Country
        CRUD::field([
            'name'    => 'country',
            'label'   => 'Country',
            'type'    => 'text',
            'default' => 'India',
        ]);

        // Is Primary
        CRUD::field([
            'name'    => 'is_primary',
            'label'   => 'Is Primary Address',
            'type'    => 'boolean',
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
