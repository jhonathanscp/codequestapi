<?php

namespace Database\Factories;

use App\Models\TutorMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TutorMessage>
 */
class TutorMessageFactory extends Factory
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
            'role' => 'user',
            'content' => fake()->paragraph(),
            'roadmap_node_id' => null,
        ];
    }

    /**
     * Mensagem do aluno.
     */
    public function fromUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
        ]);
    }

    /**
     * Mensagem do tutor IA.
     */
    public function fromAssistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'assistant',
        ]);
    }

    /**
     * Vincula a um nó específico do roadmap.
     */
    public function forNode(int $nodeId): static
    {
        return $this->state(fn (array $attributes) => [
            'roadmap_node_id' => $nodeId,
        ]);
    }
}
