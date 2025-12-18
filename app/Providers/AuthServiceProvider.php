<?php

namespace App\Providers;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * This method is called by Laravel after all service providers are registered
     * and is the place to define all of your gates and policies!
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.url') . "/password-reset/$token?email=" . urlencode($notifiable->getEmailForPasswordReset());
        });

        // Define gates for permission checking
        $this->definePermissionGates();

        // Define gates for specific resources
        $this->defineResourceGates();

        // Define gates for data scoping
        $this->defineScopeGates();
    }

    /**
     * Define permission-based gates
     *
     * Gate::check() allows you to use:
     * if (Gate::check('branch.view')) { ... }
     * if (auth()->user()->can('branch.view')) { ... }
     * @can('branch.view') ... @endcan in Blade
     */
    private function definePermissionGates(): void
    {
        // Generic permission gate
        Gate::define('permission', function (User $user, string $permission) {
            return $user->can($permission);
        });

        // Super admin gate (bypasses everything)
        Gate::define('super-admin', function (User $user) {
            return $user->isSuperAdmin();
        });

        // Admin gate
        Gate::define('admin', function (User $user) {
            return $user->hasRole(['super_admin', 'admin']);
        });
    }

    /**
     * Define resource-specific gates
     * 
     * Usage:
     * if (Gate::check('create-branch')) { ... }
     * @can('create-branch') ... @endcan
     */
    private function defineResourceGates(): void
    {
        // Branch gates
        Gate::define('view-branches', function (User $user) {
            return $user->can('branch.view');
        });

        Gate::define('create-branch', function (User $user) {
            return $user->can('branch.create');
        });

        Gate::define('edit-branch', function (User $user) {
            return $user->can('branch.edit');
        });

        Gate::define('delete-branch', function (User $user) {
            return $user->can('branch.delete');
        });

        // Location gates
        Gate::define('view-locations', function (User $user) {
            return $user->can('location.view');
        });

        Gate::define('create-location', function (User $user) {
            return $user->can('location.create');
        });

        Gate::define('edit-location', function (User $user) {
            return $user->can('location.edit');
        });

        Gate::define('delete-location', function (User $user) {
            return $user->can('location.delete');
        });

        // Department gates
        Gate::define('view-departments', function (User $user) {
            return $user->can('department.view');
        });

        Gate::define('create-department', function (User $user) {
            return $user->can('department.create');
        });

        Gate::define('edit-department', function (User $user) {
            return $user->can('department.edit');
        });

        Gate::define('delete-department', function (User $user) {
            return $user->can('department.delete');
        });

        // Add more as needed for other resources
        // (vertical, segment, brand, etc.)
    }

    /**
     * Define data scope gates
     * 
     * These gates check if user can access specific data
     * Usage:
     * if (Gate::check('view-branch', $branch)) { ... }
     * @can('view-branch', $branch) ... @endcan
     */
    private function defineScopeGates(): void
    {
        // View specific branch
        Gate::define('view-branch', function (User $user, $branch) {
            // Super admin can view any branch
            if ($user->isSuperAdmin()) {
                return true;
            }

            // Check if user has general view permission
            if (!$user->can('branch.view')) {
                return false;
            }

            // Check if user has access to this specific branch
            return $user->hasAccessTo('branch', $branch->id);
        });

        // Edit specific branch
        Gate::define('edit-branch', function (User $user, $branch) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            if (!$user->can('branch.edit')) {
                return false;
            }

            return $user->hasAccessTo('branch', $branch->id);
        });

        // Delete specific branch
        Gate::define('delete-branch', function (User $user, $branch) {
            if ($user->isSuperAdmin()) {
                return true;
            }

            if (!$user->can('branch.delete')) {
                return false;
            }

            return $user->hasAccessTo('branch', $branch->id);
        });

        // Similar gates for other resources
        Gate::define('view-location', function (User $user, $location) {
            if ($user->isSuperAdmin()) return true;
            if (!$user->can('location.view')) return false;
            return $user->hasAccessTo('location', $location->id);
        });

        Gate::define('edit-location', function (User $user, $location) {
            if ($user->isSuperAdmin()) return true;
            if (!$user->can('location.edit')) return false;
            return $user->hasAccessTo('location', $location->id);
        });

        Gate::define('delete-location', function (User $user, $location) {
            if ($user->isSuperAdmin()) return true;
            if (!$user->can('location.delete')) return false;
            return $user->hasAccessTo('location', $location->id);
        });

        Gate::define('view-department', function (User $user, $department) {
            if ($user->isSuperAdmin()) return true;
            if (!$user->can('department.view')) return false;
            return $user->hasAccessTo('department', $department->id);
        });

        Gate::define('edit-department', function (User $user, $department) {
            if ($user->isSuperAdmin()) return true;
            if (!$user->can('department.edit')) return false;
            return $user->hasAccessTo('department', $department->id);
        });

        Gate::define('delete-department', function (User $user, $department) {
            if ($user->isSuperAdmin()) return true;
            if (!$user->can('department.delete')) return false;
            return $user->hasAccessTo('department', $department->id);
        });
    }
}
