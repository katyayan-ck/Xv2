<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class UserTypeCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\UserType::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user-type');
        CRUD::setEntityNameStrings('user type', 'user types');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('user_type.view')) abort(403);
        CRUD::column('code');
        CRUD::column('display_name')->label('Name');
        CRUD::column('description')->limit(50);
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('user_type.create')) abort(403);
        CRUD::setValidation([
            'code' => 'required|string|max:5|unique:user_types,code',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        CRUD::field([
            'name'  => 'code',
            'label' => 'Code',
            'type'  => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        CRUD::field([
            'name'  => 'display_name',
            'label' => 'Name',
            'type'  => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        CRUD::field([
            'name'  => 'description',
            'label' => 'Description',
            'type'  => 'textarea',
        ]);

        CRUD::field([
            'name'    => 'is_active',
            'label'   => 'Active',
            'type'    => 'boolean',
            'default' => true,
        ]);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('user_type.edit')) abort(403);
        $this->setupCreateOperation();

        $entryId = $this->crud->getCurrentEntryId();

        CRUD::setValidation([
            'code' => "required|string|max:5|unique:user_types,code,{$entryId}",
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
    }
}
