<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class SubSegmentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\Core\SubSegment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/sub-segment');
        CRUD::setEntityNameStrings('sub segment', 'sub segments');
    }

    protected function setupListOperation()
    {
        // Hierarchy: Brand → Segment → SubSegment
        CRUD::column('segment.brand.name')->label('Brand');
        CRUD::column('segment.name')->label('Segment');
        CRUD::column('name')->label('Sub Segment Name');
        CRUD::column('code');
        CRUD::column('description')->limit(100);
        CRUD::column('is_active')->type('boolean');
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            'segment_id'  => 'required|exists:segments,id',
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|size:5|unique:sub_segments,code,NULL,id,segment_id,' . request()->input('segment_id'),
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);

        // Segment select field – simple and safe
        CRUD::field([
            'name'       => 'segment_id',
            'label'      => 'Segment',
            'type'       => 'select',
            'entity'     => 'segment',
            'attribute'  => 'name',
            'model'      => \App\Models\Core\Segment::class,
            'options'    => (function ($query) {
                return $query->orderBy('name', 'ASC')->get();
            }),
        ]);

        CRUD::field([
            'name'       => 'name',
            'label'      => 'Sub Segment Name',
            'type'       => 'text',
            'attributes' => ['required' => 'required'],
        ]);

        CRUD::field([
            'name'       => 'code',
            'label'      => 'Code (5 characters)',
            'type'       => 'text',
            'attributes' => ['maxlength' => 5, 'style' => 'text-transform:uppercase;'],
            'hint'       => 'e.g. MICRO, COMPT, PREMM, ENTRY',
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
        $this->setupCreateOperation();

        // Update के लिए code unique rule adjust
        $entryId = $this->crud->getCurrentEntryId();
        $segmentId = $this->crud->getCurrentEntry()?->segment_id ?? request()->input('segment_id');

        CRUD::setValidation([
            'segment_id'  => 'required|exists:segments,id',
            'name'        => 'required|string|max:255',
            'code'        => "required|string|size:5|unique:sub_segments,code,{$entryId},id,segment_id,{$segmentId}",
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);
    }
}
