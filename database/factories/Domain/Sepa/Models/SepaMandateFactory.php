<?php

namespace Database\Factories\Domain\Sepa\Models;

use App\Domain\Club\Models\Club;
use App\Domain\Membership\Models\Member;
use App\Domain\Sepa\Enums\SepaMandateStatus;
use App\Domain\Sepa\Models\SepaMandate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SepaMandate>
 */
class SepaMandateFactory extends Factory
{
    protected $model = SepaMandate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'member_id' => fn (array $attributes): string => Member::factory()
                ->create(['club_id' => $attributes['club_id']])
                ->getKey(),
            'status' => SepaMandateStatus::Active,
            'mandate_reference' => fake()->uuid(),
            'account_holder' => fake()->name(),
            'iban' => 'DE89370400440532013000',
            'bic' => null,
            'signed_at' => '2026-10-01',
            'valid_from' => '2026-10-01',
        ];
    }
}
