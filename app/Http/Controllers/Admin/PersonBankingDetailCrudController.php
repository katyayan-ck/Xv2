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
    // Alias the trait methods inside the class
    use CreateOperation {
        store as traitStore;
    }
    use UpdateOperation {
        update as traitUpdate;
    }
    use ListOperation, DeleteOperation;

    public function setup()
    {
        CRUD::setModel(PersonBankingDetail::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person-banking-detail');
        CRUD::setEntityNameStrings('Banking Detail', 'Banking Details');
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name'      => 'person_id',
            'label'     => 'Person',
            'type'      => 'select',
            'entity'    => 'person',
            'attribute' => 'display_name',
        ]);

        CRUD::addColumn('bank_name');
        CRUD::addColumn('account_number');
        CRUD::addColumn('account_holder_name');
        CRUD::addColumn('account_type');
        CRUD::addColumn('ifsc_code');

        CRUD::addColumn([
            'name'  => 'is_primary',
            'label' => 'Primary?',
            'type'  => 'boolean',
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'person_id'           => 'required|exists:persons,id',
            'bank_name'           => 'required|string|max:255',
            'account_number'      => 'required|string|max:255',
            'account_holder_name' => 'required|string|max:255',
            'account_type'        => 'required|in:savings,current,other',
            'ifsc_code'           => 'nullable|string|max:20',
            'is_primary'          => 'sometimes|boolean',
        ]);

        CRUD::addField([
            'name'      => 'person_id',
            'label'     => 'Person',
            'type'      => 'select',
            'entity'    => 'person',
            'model'     => Person::class,
            'attribute' => 'display_name',
        ]);

        CRUD::field('bank_name')->type('text')->label('Bank Name');
        CRUD::field('account_number')->type('text')->label('Account Number');
        CRUD::field('account_holder_name')->type('text')->label('Account Holder Name');

        CRUD::addField([
            'name'    => 'account_type',
            'label'   => 'Account Type',
            'type'    => 'select_from_array',
            'options' => [
                'savings' => 'Savings',
                'current' => 'Current',
                'other'   => 'Other',
            ],
            'default' => 'savings',
        ]);

        CRUD::field('ifsc_code')->type('text')->label('IFSC Code')->hint('Optional');

        CRUD::addField([
            'name'  => 'is_primary',
            'label' => 'Is Primary Account?',
            'type'  => 'checkbox',
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Custom store: run original logic then redirect to list
     */
    public function store()
    {
        $this->traitStore(); // Calls the original CreateOperation::store()

        return redirect($this->crud->route);
    }

    /**
     * Custom update: run original logic then redirect to list
     */
    public function update()
    {
        $this->traitUpdate(); // Calls the original UpdateOperation::update()

        return redirect($this->crud->route);
    }
}
