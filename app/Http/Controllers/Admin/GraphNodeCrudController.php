<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class GraphNodeCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\GraphNode::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/graph-node');
        CRUD::setEntityNameStrings('node', 'nodes');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('graph_node.view')) abort(403);
        CRUD::column('user.name')->label('User');
        CRUD::column('node_type');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('graph_node.create')) abort(403);
        CRUD::field('user_id')->type('select2')->entity('user')->attribute('name');
        CRUD::field('node_type')->type('select_from_array')->options(['person' => 'Person', 'role' => 'Role', 'department' => 'Department']);
        CRUD::field('is_active')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('graph_node.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
