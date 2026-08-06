<?php

namespace App\Providers;

use App\Models\FooterSetting;
use App\Models\WebsiteSetting;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(\App\Repositories\CompanyRepositoryInterface::class, \App\Repositories\CompanyRepository::class);
        $this->app->bind(\App\Repositories\BranchRepositoryInterface::class, \App\Repositories\BranchRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Implicitly grant "Super Admin" role all permissions globally
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

        // Share WebsiteSetting and FooterSetting with all public layouts and components
        View::composer(['layouts.public', 'components.layouts.public-*', 'public.*'], function ($view) {
            try {
                $settings = WebsiteSetting::first();
                $footer = FooterSetting::first();
                $view->with('settings', $settings);
                $view->with('footer', $footer);
            } catch (\Throwable $e) {
                $view->with('settings', null);
                $view->with('footer', null);
            }
        });
    }
}
