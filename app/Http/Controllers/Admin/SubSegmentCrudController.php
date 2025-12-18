<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class SubSegmentCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\SubSegment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/sub-segment');
        CRUD::setEntityNameStrings('sub-segment', 'sub-segments');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('sub_segment.view')) abort(403);
        CRUD::column('segment.name')->label('Segment');
        CRUD::column('name');
        CRUD::column('slug');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('sub_segment.create')) abort(403);
        CRUD::field('segment_id')->type('select2')->entity('segment')->attribute('name');
        CRUD::field('name')->required();
        CRUD::field('slug')->hint('Auto-generated from name');
        CRUD::field('description')->type('textarea');
        CRUD::field('is_active')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('sub_segment.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
