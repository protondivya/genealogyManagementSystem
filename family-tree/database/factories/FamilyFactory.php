<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Family>
 */
class FamilyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->lastName().' Family',
            'description' => fake()->sentence(),
            'owner_id' => User::factory(),
        ];
    }
}
