<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberTermFactory extends Factory
{
    protected $model = MemberTerm::class;

    public function definition(): array
    {
        $startYear = $this->faker->numberBetween(2000, 2024);

        return [
            'member_id' => Member::factory(),
            'chamber' => $this->faker->randomElement(['House of Representatives', 'Senate']),
            'start_year' => $startYear,
            'end_year' => $this->faker->optional(0.7)->numberBetween($startYear, $startYear + 6),
        ];
    }
}
