<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\RBACService;
use App\Services\DataScopeService;
use App\Services\AuthService;
use App\Services\ApprovalService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // // Register services as singletons for performance
        // $this->app->singleton(RBACService::class, function ($app) {
        //     return new RBACService();
        // });

        // $this->app->singleton(DataScopeService::class, function ($app) {
        //     return new DataScopeService();
        // });

        // $this->app->singleton(AuthService::class, function ($app) {
        //     return new AuthService();
        // });

        // $this->app->singleton(ApprovalService::class, function ($app) {
        //     return new ApprovalService();
        // });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
