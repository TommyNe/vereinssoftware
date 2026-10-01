<?php

namespace App\Providers;

use App\Application\Audit\ModelAuditObserver;
use App\Application\Audit\UserSecurityObserver;
use App\Application\Club\CurrentClub;
use App\Domain\Club\Models\Club;
use App\Domain\Club\Models\ClubInvitation;
use App\Domain\Contribution\Models\ContributionCharge;
use App\Domain\Contribution\Models\ContributionRate;
use App\Domain\Contribution\Models\ContributionRunError;
use App\Domain\Contribution\Models\ContributionType;
use App\Domain\Contribution\Models\MemberContributionOverride;
use App\Domain\Membership\Models\ClubFunction;
use App\Domain\Membership\Models\Department;
use App\Domain\Membership\Models\Member;
use App\Domain\Membership\Models\MemberDocument;
use App\Domain\Membership\Models\MembershipType;
use App\Models\User;
use App\Policies\ClubFunctionPolicy;
use App\Policies\ClubInvitationPolicy;
use App\Policies\ClubPolicy;
use App\Policies\ContributionChargePolicy;
use App\Policies\ContributionRatePolicy;
use App\Policies\ContributionRunErrorPolicy;
use App\Policies\ContributionTypePolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\MemberPolicy;
use App\Policies\MembershipTypePolicy;
use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\VerifyEmail;
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
        foreach ([ContributionType::class, ContributionRate::class, MemberContributionOverride::class, MemberDocument::class, MembershipType::class, Department::class, ClubFunction::class] as $modelClass) {
            $modelClass::observe(ModelAuditObserver::class);
        }

        User::observe(UserSecurityObserver::class);

        Gate::policy(ContributionRate::class, ContributionRatePolicy::class);
        Gate::policy(ContributionType::class, ContributionTypePolicy::class);
        Gate::policy(ContributionCharge::class, ContributionChargePolicy::class);
        Gate::policy(ContributionRunError::class, ContributionRunErrorPolicy::class);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        VerifyEmail::createUrlUsing(
            static fn ($notifiable): string => Filament::getVerifyEmailUrl($notifiable)
        );

        Gate::policy(
            Member::class,
            MemberPolicy::class,
        );

        Gate::policy(
            Club::class,
            ClubPolicy::class,
        );

        Gate::policy(
            MembershipType::class,
            MembershipTypePolicy::class,
        );

        Gate::policy(
            Department::class,
            DepartmentPolicy::class,
        );

        Gate::policy(
            ClubFunction::class,
            ClubFunctionPolicy::class,
        );

        Gate::policy(
            ClubInvitation::class,
            ClubInvitationPolicy::class,
        );
    }
}
