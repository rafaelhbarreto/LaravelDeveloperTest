<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'bioguide_id' => strtoupper($this->faker->unique()->bothify('?######')),
            'name' => $this->faker->name(),
            'party_name' => $this->faker->randomElement(['Democratic', 'Republican', 'Independent']),
            'state' => $this->faker->stateAbbr(),
            'district' => $this->faker->numberBetween(1, 50),
            'updated_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'url' => $this->faker->url(),
            'depiction_attribution' => $this->faker->company(),
            'depiction_image_url' => $this->faker->imageUrl(),
        ];
    }
}
