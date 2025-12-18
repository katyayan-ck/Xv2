<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

class GraphEdgeCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\GraphEdge::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/graph-edge');
        CRUD::setEntityNameStrings('edge', 'edges');
    }

    protected function setupListOperation()
    {
        //if (!backpack_user()->can('graph_edge.view')) abort(403);
        CRUD::column('from_node_id');
        CRUD::column('to_node_id');
        CRUD::column('type');
        CRUD::column('level');
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        //if (!backpack_user()->can('graph_edge.create')) abort(403);
        CRUD::field('from_node_id')->type('select2')->entity('fromNode')->attribute('name');
        CRUD::field('to_node_id')->type('select2')->entity('toNode')->attribute('name');
        CRUD::field('type')->type('select_from_array')->options(['reports_to' => 'Reports To', 'approves' => 'Approves']);
        CRUD::field('level')->type('number');
        CRUD::field('powers_json')->type('json')->hint('Optional permissions');
        CRUD::field('is_active')->type('boolean')->default(true);
    }

    protected function setupUpdateOperation()
    {
        //if (!backpack_user()->can('graph_edge.edit')) abort(403);
        $this->setupCreateOperation();
    }
}
