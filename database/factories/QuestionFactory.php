<?php

namespace Database\Factories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'technical',
            'content' => fake()->sentence(),
            'options' => [
                'A' => fake()->sentence(),
                'B' => fake()->sentence(),
                'C' => fake()->sentence(),
                'D' => fake()->sentence(),
            ],
            'correct_answer' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'order' => fake()->numberBetween(1, 10),
        ];
    }

    /**
     * Questão do tipo perfil (sem resposta correta).
     */
    public function profile(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'profile',
            'correct_answer' => null,
        ]);
    }

    /**
     * Questão do tipo técnico.
     */
    public function technical(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'technical',
        ]);
    }
}
