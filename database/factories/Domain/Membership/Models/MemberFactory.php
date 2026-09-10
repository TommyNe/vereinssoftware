<?php

namespace Database\Factories\Domain\Membership\Models;

use App\Domain\Membership\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'club_id' => Str::uuid(),
            'member_number' => fake()->numerify('####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-80 years', '-18 years')->format('Y-m-d'),
            'joined_at' => fake()->dateTime()->format('Y-m-d'),
            'status' => 'active',
        ];
    }

    public function create($attributes = [], ?\Illuminate\Database\Eloquent\Model $parent = null)
    {
        $records = $this->make($attributes, $parent);

        if ($records instanceof \Illuminate\Database\Eloquent\Model) {
            $this->store(collect([$records]));
            $records->refresh();

            $this->callAfterCreating(collect([$records]));

            return $records;
        }

        $this->store($records);

        return $records->map(function ($record) {
            $record->refresh();

            return $record;
        })->all();
    }

    protected function store($records)
    {
        $records->each(function (Member $record) {
            $record->writeable()->save();
        });
    }
}


