<?php

namespace App\Providers;

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Policies\ClubPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            CurrentClub::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(
            Club::class,
            ClubPolicy::class,
        );
    }
}
