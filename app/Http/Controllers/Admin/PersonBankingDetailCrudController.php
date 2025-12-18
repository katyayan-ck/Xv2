<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class PersonBankingDetailCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\PersonBankingDetail::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/person-banking-detail');
        CRUD::setEntityNameStrings('banking detail', 'banking details');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('person_banking_detail.view')) abort(403);
        CRUD::column('person.name')->label('Person');
        CRUD::column('bank_name');
        CRUD::column('account_number');
        CRUD::column('account_holder_name');
        CRUD::column('is_primary')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('person_banking_detail.create')) abort(403);
        CRUD::field('person_id')->type('select2')->entity('person')->attribute('name');
        CRUD::field('bank_name')->required();
        CRUD::field('account_number')->required();
        CRUD::field('account_holder_name')->required();
        CRUD::field('account_type')->type('select_from_array')->options(['savings' => 'Savings', 'current' => 'Current', 'other' => 'Other']);
        CRUD::field('ifsc_code');
        CRUD::field('is_primary')->type('boolean');
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('person_banking_detail.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
