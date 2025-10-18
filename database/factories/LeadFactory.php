<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => '1',
            'correlation_id' => fake()->uuid(),
            'email' => fake()->unique()->safeEmail(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->company(),
            'source' => fake()->randomElement(['website', 'referral', 'social', 'email', 'phone']),
            'metadata' => [
                'utm_source' => fake()->randomElement(['google', 'facebook', 'twitter', 'linkedin']),
                'utm_medium' => fake()->randomElement(['cpc', 'organic', 'social', 'email']),
                'utm_campaign' => fake()->words(2, true),
            ],
        ];
    }
}
