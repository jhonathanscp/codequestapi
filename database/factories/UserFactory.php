<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'xp_total' => 0,
            'nivel' => 1,
            'stack_interesse' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Set a custom stack de interesse.
     */
    public function withStack(array $stack): static
    {
        return $this->state(fn (array $attributes) => [
            'stack_interesse' => $stack,
        ]);
    }

    /**
     * Set a custom XP total and nivel.
     */
    public function withXp(int $xp, int $nivel = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'xp_total' => $xp,
            'nivel' => $nivel,
        ]);
    }
}
