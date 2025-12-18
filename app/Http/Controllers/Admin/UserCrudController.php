<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Requests\UserRequest;

class UserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('user', 'users');
    }

    protected function setupListOperation()
    {
        if (!backpack_user()->can('user.view')) abort(403);

        CRUD::column('code');
        CRUD::column('name');
        CRUD::column('email');
        CRUD::column('person.mobile_primary')->label('Mobile');    // ✅ FIXED - From Person relation
        CRUD::column('last_login_at');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidationRequest(UserRequest::class);

        CRUD::field('code');
        CRUD::field('name')->required();
        CRUD::field('email')->required()->unique();
        CRUD::field('person.mobile_primary')->label('Mobile');     // ✅ FIXED - From Person relation
        CRUD::field('password')->type('password')->required();
        CRUD::field('person_id')->type('select2')->entity('person')->attribute('display_name')->hint('Link to Person (optional)');
        CRUD::field('employee_id')->type('select2')->entity('employee')->attribute('code')->hint('Link to Employee (optional)');
        CRUD::field('is_active')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation()
    {
        CRUD::setValidationRequest(UserRequest::class);

        CRUD::field('code');
        CRUD::field('name')->required();
        CRUD::field('email')->required();
        CRUD::field('person.mobile_primary')->label('Mobile');     // ✅ FIXED - From Person relation
        CRUD::field('password')->type('password')->hint('Leave blank to keep current password');
        CRUD::field('is_active')->type('boolean');
    }
}
