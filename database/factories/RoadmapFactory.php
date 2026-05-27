<?php

namespace Database\Factories;

use App\Models\Roadmap;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Roadmap>
 */
class RoadmapFactory extends Factory
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
            'nivel_calculado' => fake()->randomElement(['iniciante', 'intermediario', 'avancado']),
            'pontuacao_tecnica' => fake()->numberBetween(0, 4),
            'trilha_json' => [
                'trilha' => [
                    'titulo' => 'Trilha de Teste',
                    'modulos' => [],
                ],
            ],
        ];
    }
}
