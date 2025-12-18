<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use App\Models\Core\Branch;
use App\Models\Core\Location;
use App\Models\Core\Department;
//use App\Models\Core\Enquiry;
//use App\Models\Core\Booking;
//use App\Models\Core\Sale;
use App\Models\Core\Employee;
use App\Models\User;

class DashboardController extends CrudController
{
    public function index()
    {
        $user = backpack_user();

        if ($user->isSuperAdmin()) {
            $totalBranches = Branch::count();
            $totalLocations = Location::count();
            $totalDepartments = Department::count();
            $totalEmployees = Employee::count();
            $totalEnquiries = 0; //Enquiry::count();
            $totalBookings = 0; //Booking::count();
            $totalSales = 0; //Sale::sum('total_amount') ?? 0;
            $todayEnquiries = 0; //Enquiry::whereDate('created_at', today())->count();
            $todayBookings = 0; //Booking::whereDate('created_at', today())->count();
            $todaySales = 0; //Sale::whereDate('created_at', today())->sum('total_amount') ?? 0;

            $userAccessLabel = 'Full Access (Super Admin)';
            $showMyAccessSection = false;
        } else {
            $branchIds = $user->getAccessibleBranches();
            $locationIds = $user->getAccessibleLocations();
            $departmentIds = $user->getAccessibleDepartments();

            $totalBranches = $this->getCountWithAccess(Branch::class, $branchIds);
            $totalLocations = $this->getCountWithAccess(Location::class, $locationIds);
            $totalDepartments = $this->getCountWithAccess(Department::class, $departmentIds);
            $totalEmployees = Employee::count();
            $totalEnquiries = 0; //Enquiry::count();
            $totalBookings = 0; //Booking::count();
            $totalSales = 0; //Sale::sum('total_amount') ?? 0;

            $todayEnquiries = 0; //Enquiry::whereDate('created_at', today())->count();
            $todayBookings = 0; //Booking::whereDate('created_at', today())->count();
            $todaySales = 0; //Sale::whereDate('created_at', today())->sum('total_amount') ?? 0;

            $userAccessLabel = 'Scoped Access';
            $showMyAccessSection = true;
        }

        $recentEnquiries = []; //Enquiry::latest('created_at')->limit(5)->get();
        $recentBookings = []; //Booking::latest('created_at')->limit(5)->get();
        $activeUsers = User::where('is_active', true)->count();

        return view('admin.dashboard', [
            'user' => $user,
            'userAccessLabel' => $userAccessLabel,
            'showMyAccessSection' => $showMyAccessSection,
            'totalBranches' => $totalBranches,
            'totalLocations' => $totalLocations,
            'totalDepartments' => $totalDepartments,
            'totalEmployees' => $totalEmployees,
            'totalEnquiries' => $totalEnquiries,
            'totalBookings' => $totalBookings,
            'totalSales' => $totalSales,
            'todayEnquiries' => $todayEnquiries,
            'todayBookings' => $todayBookings,
            'todaySales' => $todaySales,
            'recentEnquiries' => $recentEnquiries,
            'recentBookings' => $recentBookings,
            'activeUsers' => $activeUsers,
        ]);
    }

    private function getCountWithAccess($modelClass, $accessibleIds)
    {
        if ($accessibleIds === null) {
            return $modelClass::count();
        }

        if ($accessibleIds === [] || empty($accessibleIds)) {
            return 0;
        }

        return $modelClass::whereIn('id', $accessibleIds)->count();
    }
}
