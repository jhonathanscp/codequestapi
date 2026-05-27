<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\XpLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<XpLog>
 */
class XpLogFactory extends Factory
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
            'amount' => fake()->numberBetween(10, 200),
            'reason' => fake()->randomElement([
                'completed_node',
                'code_approved',
                'assessment_bonus',
                'challenge_solved',
            ]),
        ];
    }

    /**
     * Define XP por nó de roadmap concluído.
     */
    public function completedNode(int $amount = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'reason' => 'completed_node',
        ]);
    }

    /**
     * Define XP por código aprovado no corretor.
     */
    public function codeApproved(int $amount = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
            'reason' => 'code_approved',
        ]);
    }
}
