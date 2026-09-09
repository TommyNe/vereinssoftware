<?php

namespace Database\Factories\Domain\Club\Models;

use App\Domain\Club\Models\Club;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Club>
 */
class ClubFactory extends Factory
{
    protected $model = Club::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
        ];
    }
}
