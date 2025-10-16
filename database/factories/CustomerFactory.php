<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Token;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nid_no' => fake()->numerify('##########'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => fake()->numerify('01#########'),
            'present_address_id' => Address::factory(),
            'permanent_address_id' => Address::factory(),
            'token_id' => Token::factory(),
            'product_type' => fake()->randomElement(['Mobile', 'Laptop', 'Tablet']),
            'product_model' => fake()->word(),
            'product_price' => fake()->randomFloat(2, 10000, 100000),
            'down_payment' => fake()->randomFloat(2, 1000, 10000),
            'emi_duration_months' => fake()->randomElement([6, 12, 18, 24]),
            'emi_per_month' => fake()->randomFloat(2, 1000, 5000),
            'serial_number' => fake()->unique()->bothify('???########'),
            'imei_1' => fake()->unique()->numerify('###############'),
            'imei_2' => fake()->unique()->numerify('###############'),
            'fcm_token' => fake()->unique()->sha256(),
            'is_device_locked' => false,
            'is_camera_disabled' => false,
            'is_bluetooth_disabled' => false,
            'is_app_hidden' => false,
            'has_password' => false,
            'created_by' => User::factory(),
            'status' => 'active',
        ];
    }
}
