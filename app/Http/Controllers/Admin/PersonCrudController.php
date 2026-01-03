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

    /**
     * Configure the CrudPanel object.
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Core\Person::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person');
        CRUD::setEntityNameStrings('person', 'persons');
    }

    /**
     * List operation - display all relevant fields from DB.
     */
    protected function setupListOperation()
    {
        CRUD::column('code');
        CRUD::column('salutation');
        CRUD::column('first_name');
        CRUD::column('middle_name');
        CRUD::column('last_name');
        CRUD::column('display_name');
        CRUD::column('gender');
        CRUD::column('dob')->label('Date of Birth');
        CRUD::column('marital_status');
        CRUD::column('spouse_name');
        CRUD::column('occupation');
        CRUD::column('aadhaar_no');
        CRUD::column('pan_no');
        CRUD::column('gst_no');
        CRUD::column('email_primary')->label('Email');
        CRUD::column('email_secondary');
        CRUD::column('mobile_primary')->label('Mobile');
        CRUD::column('mobile_secondary');
        CRUD::column('address_line1');
        CRUD::column('address_line2');
        CRUD::column('city');
        CRUD::column('state');
        CRUD::column('pincode');
        CRUD::column('country');
        CRUD::column('is_active')->type('boolean');
    }

    /**
     * Create & Update operations - all DB fields included.
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(PersonRequest::class);

        // Code
        CRUD::field([
            'name'  => 'code',
            'label' => 'Code',
            'type'  => 'text',
        ]);

        // Salutation
        CRUD::field([
            'name'    => 'salutation',
            'label'   => 'Salutation',
            'type'    => 'select_from_array',
            'options' => ['Mr' => 'Mr', 'Mrs' => 'Mrs', 'Ms' => 'Ms', 'Dr' => 'Dr'],
            'allows_null' => true,
        ]);

        // First Name (required)
        CRUD::field([
            'name'       => 'first_name',
            'label'      => 'First Name',
            'type'       => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        // Middle Name
        CRUD::field([
            'name'  => 'middle_name',
            'label' => 'Middle Name',
            'type'  => 'text',
        ]);

        // Last Name (required)
        CRUD::field([
            'name'       => 'last_name',
            'label'      => 'Last Name',
            'type'       => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        // Display Name
        CRUD::field([
            'name'  => 'display_name',
            'label' => 'Display Name',
            'type'  => 'text',
        ]);

        // Gender
        CRUD::field([
            'name'    => 'gender',
            'label'   => 'Gender',
            'type'    => 'select_from_array',
            'options' => ['male' => 'Male', 'female' => 'Female', 'other' => 'Other', 'prefer_not_to_say' => 'Prefer not to say'],
        ]);

        // Date of Birth
        CRUD::field([
            'name'  => 'dob',
            'label' => 'Date of Birth',
            'type'  => 'date',
        ]);

        // Marital Status
        CRUD::field([
            'name'    => 'marital_status',
            'label'   => 'Marital Status',
            'type'    => 'select_from_array',
            'options' => ['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed'],
            'allows_null' => true,
        ]);

        // Spouse Name
        CRUD::field([
            'name'  => 'spouse_name',
            'label' => 'Spouse Name',
            'type'  => 'text',
        ]);

        // Occupation
        CRUD::field([
            'name'  => 'occupation',
            'label' => 'Occupation',
            'type'  => 'text',
        ]);

        // Aadhaar No
        CRUD::field([
            'name'  => 'aadhaar_no',
            'label' => 'Aadhaar No',
            'type'  => 'text',
        ]);

        // PAN No
        CRUD::field([
            'name'  => 'pan_no',
            'label' => 'PAN No',
            'type'  => 'text',
        ]);

        // GST No
        CRUD::field([
            'name'  => 'gst_no',
            'label' => 'GST No',
            'type'  => 'text',
        ]);

        // Email Primary
        CRUD::field([
            'name'  => 'email_primary',
            'label' => 'Email Primary',
            'type'  => 'email',
        ]);

        // Email Secondary
        CRUD::field([
            'name'  => 'email_secondary',
            'label' => 'Email Secondary',
            'type'  => 'email',
        ]);

        // Mobile Primary
        CRUD::field([
            'name'  => 'mobile_primary',
            'label' => 'Mobile Primary',
            'type'  => 'text',
        ]);

        // Mobile Secondary
        CRUD::field([
            'name'  => 'mobile_secondary',
            'label' => 'Mobile Secondary',
            'type'  => 'text',
        ]);

        // Address Line 1
        CRUD::field([
            'name'  => 'address_line1',
            'label' => 'Address Line 1',
            'type'  => 'text',
        ]);

        // Address Line 2
        CRUD::field([
            'name'  => 'address_line2',
            'label' => 'Address Line 2',
            'type'  => 'text',
        ]);

        // City
        CRUD::field([
            'name'  => 'city',
            'label' => 'City',
            'type'  => 'text',
        ]);

        // State
        CRUD::field([
            'name'  => 'state',
            'label' => 'State',
            'type'  => 'text',
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

        // Active
        CRUD::field([
            'name'    => 'is_active',
            'label'   => 'Active',
            'type'    => 'boolean',
            'default' => true,
        ]);
    }

    /**
     * Use the same fields for Update as Create.
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
