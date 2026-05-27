<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\User;
use App\Models\UserAnswer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAnswer>
 */
class UserAnswerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'question_id' => Question::factory(),
            'answer' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'is_correct' => fake()->boolean(),
        ];
    }
}
