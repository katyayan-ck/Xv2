<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use App\Models\Core\Role;           // ← USE CUSTOM MODEL
use App\Models\Core\Permission;     // ← USE CUSTOM MODEL

class RoleCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(Role::class);  // ← USE CUSTOM ROLE MODEL
        CRUD::setRoute(config('backpack.base.route_prefix') . '/role');
        CRUD::setEntityNameStrings('role', 'roles');
    }

    protected function setupListOperation()
    {
        if (!backpack_user()->can('role.view')) abort(403);

        CRUD::column('id')->label('ID');
        CRUD::column('name')->label('Role Name');
        CRUD::column('guard_name')->label('Guard');
        CRUD::column('created_at')->type('datetime')->label('Created');
        CRUD::column('permissions_count')->type('text')->label('Permissions')
            ->searchLogic(false)
            ->orderable(false);
    }

    protected function setupCreateOperation()
    {
        if (!backpack_user()->can('role.create')) abort(403);

        CRUD::field('name')
            ->required()
            ->unique()
            ->hint('The role name (e.g., Manager, Editor, Admin)')
            ->wrapperAttributes(['class' => 'form-group col-md-6']);

        CRUD::field('guard_name')
            ->type('select_from_array')
            ->options(['web' => 'Web', 'api' => 'API'])
            ->default('web')
            ->hint('The guard for this role')
            ->wrapperAttributes(['class' => 'form-group col-md-6']);

        CRUD::field('permissions')
            ->type('select_multiple')
            ->attribute('name')
            ->model(Permission::class)  // ← USE CUSTOM PERMISSION MODEL
            ->pivot(true)
            ->label('Assign Permissions')
            ->hint('Select permissions to assign to this role');
    }

    protected function setupUpdateOperation()
    {
        if (!backpack_user()->can('role.edit')) abort(403);
        $this->setupCreateOperation();
    }

    protected function setupDeleteOperation()
    {
        if (!backpack_user()->can('role.delete')) abort(403);
    }
}
