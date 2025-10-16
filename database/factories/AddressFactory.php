<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\Division;
use App\Models\Upazilla;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'street_address' => fake()->address(),
            'landmark' => fake()->streetName(),
            'postal_code' => fake()->postcode(),
            'division_id' => Division::first()->id ?? 1,
            'district_id' => District::first()->id ?? 1,
            'upazilla_id' => Upazilla::first()->id ?? 1,
        ];
    }
}
