<?php

use App\Domain\Contribution\Models\ContributionRunError;
use App\Models\User;
use App\Policies\ContributionRunErrorPolicy;
use App\Policies\ContributionRunPolicy;
use Illuminate\Support\Facades\Gate;

it('does not allow bulk deletion of contribution runs', function (): void {
    expect(app(ContributionRunPolicy::class)->deleteAny(User::factory()->make()))
        ->toBeFalse();
});

it('registers a policy for contribution run errors', function (): void {
    expect(Gate::getPolicyFor(ContributionRunError::class))
        ->toBeInstanceOf(ContributionRunErrorPolicy::class);
});
