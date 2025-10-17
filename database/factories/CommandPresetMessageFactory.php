<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommandPresetMessage>
 */
class CommandPresetMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $commands = [
            'LOCK_DEVICE',
            'UNLOCK_DEVICE',
            'DISABLE_CAMERA',
            'ENABLE_CAMERA',
            'DISABLE_BLUETOOTH',
            'ENABLE_BLUETOOTH',
            'DISABLE_CALL',
            'ENABLE_CALL',
        ];

        return [
            'user_id' => \App\Models\User::factory(),
            'command' => fake()->randomElement($commands),
            'title' => fake()->sentence(3),
            'message' => fake()->sentence(),
            'is_active' => fake()->boolean(80),
        ];
    }

    /**
     * Indicate that the preset message is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the preset message is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set preset message for a specific command
     */
    public function forCommand(string $command): static
    {
        return $this->state(fn (array $attributes) => [
            'command' => $command,
        ]);
    }
}
