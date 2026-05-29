<?php

namespace App\Providers;

use App\Enums\EmployeeRole;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Observers\LeaveRequestObserver;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch
                ->locales(['en', 'id']); // also accepts a closure
        });

        LeaveRequest::observe(LeaveRequestObserver::class);

        Gate::define('manage_settings', function (User $user) {
            return in_array($user->employee?->role, [
                EmployeeRole::ADMIN,
                EmployeeRole::HRD,
            ]);
        });
    }
}
