<?php

namespace App\Http\Controllers\Admin;

use App\Models\Core\Person;
use App\Models\Core\PersonBankingDetail;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class PersonBankingDetailCrudController extends CrudController
{
    use ListOperation, CreateOperation, UpdateOperation, DeleteOperation;

    public function setup()
    {
        CRUD::setModel(PersonBankingDetail::class);

        // ⚠️ ROUTE SAME RAHEGA
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person-banking-detail');

        CRUD::setEntityNameStrings('Banking Detail', 'Banking Details');
    }

    /**
     * LIST
     */
    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name'      => 'person_id',
            'label'     => 'Person',
            'type'      => 'select',
            'entity'    => 'person',
            'attribute' => 'display_name', // accessor
        ]);

        CRUD::column('bank_name');
        CRUD::column('account_number');
        CRUD::column('account_holder_name');
        CRUD::column('is_primary')->type('boolean');
    }

    /**
     * CREATE
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'person_id'           => 'required|exists:persons,id',
            'bank_name'           => 'required',
            'account_number'      => 'required',
            'account_holder_name' => 'required',
        ]);

        // 🔥 THIS FIXES THE ERROR
        CRUD::addField([
            'name'      => 'person_id',
            'label'     => 'Person',
            'type'      => 'select',
            'entity'    => 'person',
            'model'     => Person::class,
            'attribute' => 'display_name', // NOT "name"
        ]);

        CRUD::addField([
            'name'  => 'bank_name',
            'type'  => 'text',
            'label' => 'Bank Name',
        ]);

        CRUD::addField([
            'name'  => 'account_number',
            'type'  => 'text',
            'label' => 'Account Number',
        ]);

        CRUD::addField([
            'name'  => 'account_holder_name',
            'type'  => 'text',
            'label' => 'Account Holder Name',
        ]);

        CRUD::addField([
            'name'    => 'account_type',
            'type'    => 'select_from_array',
            'options' => [
                'savings' => 'Savings',
                'current' => 'Current',
                'other'   => 'Other',
            ],
        ]);

        CRUD::addField([
            'name'  => 'ifsc_code',
            'type'  => 'text',
            'label' => 'IFSC Code',
        ]);

        CRUD::addField([
            'name'    => 'is_primary',
            'type'    => 'boolean',
            'default' => true,
        ]);
    }

    public function store()
    {
        $response = parent::store();
        return redirect($this->crud->route);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
