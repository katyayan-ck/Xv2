{{-- MAIN ADMIN MENU - ALL ITEMS NESTED --}}
<x-backpack::menu-dropdown title="Admin" icon="la la-th">

    {{-- Dashboard --}}
    <x-backpack::menu-dropdown-item title="Dashboard" icon="la la-home" :link="backpack_url('dashboard')" />

    {{-- Separator --}}
    <x-backpack::menu-separator title="Configuration" />

    {{-- Utilities Section --}}
    <x-backpack::menu-dropdown title="Utilities" icon="la la-wrench" nested="true">
        <x-backpack::menu-dropdown-item title="Keyword Master" icon="la la-tag" :link="backpack_url('keyword-master')" />
        <x-backpack::menu-dropdown-item title="Key Values" icon="la la-key" :link="backpack_url('keyvalue')" />
    </x-backpack::menu-dropdown>

    {{-- Foundation Section --}}
    <x-backpack::menu-dropdown title="Foundation" icon="la la-building" nested="true">
        <x-backpack::menu-dropdown-item title="Branch" icon="la la-code-branch" :link="backpack_url('branch')" />
        <x-backpack::menu-dropdown-item title="Location" icon="la la-map-marker" :link="backpack_url('location')" />
        <x-backpack::menu-dropdown-item title="Department" icon="la la-layer-group" :link="backpack_url('department')" />
        <x-backpack::menu-dropdown-item title="Division" icon="la la-layer-group" :link="backpack_url('division')" />
        <x-backpack::menu-dropdown-item title="Designation" icon="la la-id-badge" :link="backpack_url('designation')" />
        <x-backpack::menu-dropdown-item title="Post" icon="la la-file-alt" :link="backpack_url('post')" />
        <x-backpack::menu-dropdown-item title="Vertical" icon="la la-bars" :link="backpack_url('vertical')" />
    </x-backpack::menu-dropdown>

    {{-- Vehicles Info Section --}}
    <x-backpack::menu-dropdown title="Vehicles Info" icon="la la-car" nested="true">
        <x-backpack::menu-dropdown-item title="Brand" icon="la la-trademark" :link="backpack_url('brand')" />
        <x-backpack::menu-dropdown-item title="Segment" icon="la la-rectangle-wide" :link="backpack_url('segment')" />
        <x-backpack::menu-dropdown-item title="Sub Segment" icon="la la-rectangle-narrow" :link="backpack_url('sub-segment')" />
        <x-backpack::menu-dropdown-item title="Vehicle Model" icon="la la-cube" :link="backpack_url('vehicle-model')" />
        <x-backpack::menu-dropdown-item title="Variant" icon="la la-clone" :link="backpack_url('variant')" />
        <x-backpack::menu-dropdown-item title="Color" icon="la la-palette" :link="backpack_url('color')" />
    </x-backpack::menu-dropdown>

    {{-- Separator --}}
    <x-backpack::menu-separator title="Users & Organization" />

    {{-- Users Info Section --}}
    <x-backpack::menu-dropdown title="Users Info" icon="la la-users" nested="true">
        <x-backpack::menu-dropdown-item title="User Type" icon="la la-user-tag" :link="backpack_url('user-type')" />
        <x-backpack::menu-dropdown-item title="Person" icon="la la-user-circle" :link="backpack_url('person')" />
        <x-backpack::menu-dropdown-item title="Person Contact" icon="la la-phone" :link="backpack_url('person-contact')" />
        <x-backpack::menu-dropdown-item title="Person Address" icon="la la-map-pin" :link="backpack_url('person-address')" />
        <x-backpack::menu-dropdown-item title="Person Banking Detail" icon="la la-university" :link="backpack_url('person-banking-detail')" />
        <x-backpack::menu-dropdown-item title="Garage" icon="la la-warehouse" :link="backpack_url('garage')" />
        <x-backpack::menu-dropdown-item title="User" icon="la la-user" :link="backpack_url('user')" />

        {{-- 4-LEVEL NESTED: Employee Info --}}
        <x-backpack::menu-dropdown title="Employee Info" icon="la la-sitemap" nested="true">
            <x-backpack::menu-dropdown-item title="Employee" icon="la la-user-tie" :link="backpack_url('employee')" />
            <x-backpack::menu-dropdown-item title="Employee Department Assignment" icon="la la-link"
                :link="backpack_url('employee-department-assignment')" />
            <x-backpack::menu-dropdown-item title="Employee Branch Assignment" icon="la la-link" :link="backpack_url('employee-branch-assignment')" />
            <x-backpack::menu-dropdown-item title="Employee Location Assignment" icon="la la-link" :link="backpack_url('employee-location-assignment')" />
            <x-backpack::menu-dropdown-item title="Employee Vertical Assignment" icon="la la-link" :link="backpack_url('employee-vertical-assignment')" />
            <x-backpack::menu-dropdown-item title="Employee Post Assignment" icon="la la-link" :link="backpack_url('employee-post-assignment')" />
        </x-backpack::menu-dropdown>
    </x-backpack::menu-dropdown>

    {{-- Separator --}}
    <x-backpack::menu-separator title="Access Control" />

    {{-- RBAC Section --}}
    <x-backpack::menu-dropdown title="RBAC" icon="la la-lock" nested="true">
        <x-backpack::menu-dropdown-item title="Modules" icon="la la-cube" :link="backpack_url('modules')" />
        <x-backpack::menu-dropdown-item title="Process" icon="la la-cogs" :link="backpack_url('process')" />
        <x-backpack::menu-dropdown-item title="Role" icon="la la-users" :link="backpack_url('role')" />
        <x-backpack::menu-dropdown-item title="Permission" icon="la la-key" :link="backpack_url('permission')" />
        <x-backpack::menu-dropdown-item title="Post Permission" icon="la la-check-circle" :link="backpack_url('post-permission')" />
        <x-backpack::menu-dropdown-item title="Graph Node" icon="la la-project-diagram" :link="backpack_url('graph-node')" />
        <x-backpack::menu-dropdown-item title="Graph Edge" icon="la la-bezier-curve" :link="backpack_url('graph-edge')" />
        <x-backpack::menu-dropdown-item title="Reporting Hierarchy" icon="la la-sitemap" :link="backpack_url('reporting-hierarchy')" />
        <x-backpack::menu-dropdown-item title="Approval Hierarchy" icon="la la-shield-alt" :link="backpack_url('approval-hierarchy')" />
    </x-backpack::menu-dropdown>

</x-backpack::menu-dropdown>
