<?php

namespace App\Providers;

use App\Enums\EmployeeRole;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Observers\LeaveRequestObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        LeaveRequest::observe(LeaveRequestObserver::class);

        Gate::define('manage_settings', function (User $user) {
            return in_array($user->employee?->role, [
                EmployeeRole::ADMIN,
                EmployeeRole::HRD,
            ]);
        });
    }
}
