<?php

namespace App\Providers;

use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Membership\Models\Member;
use App\Policies\ClubPolicy;
use App\Policies\MemberPolicy;
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
        $this->app->singleton(
            CurrentClub::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::policy(
            Member::class,
            MemberPolicy::class,
        );

        Gate::policy(
            Club::class,
            ClubPolicy::class,
        );
    }
}
