<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Popula as 5 questões fixas de nivelamento.
     * 1 pergunta de perfil + 4 perguntas técnicas.
     */
    public function run(): void
    {
        $questions = [
            // ── Pergunta de Perfil ───────────────────────────────────────
            [
                'type' => 'profile',
                'content' => 'Em qual período do curso de Ciência da Computação / Tecnologia você está?',
                'options' => [
                    'A' => '1º ou 2º período (início do curso)',
                    'B' => '3º ou 4º período',
                    'C' => '5º ou 6º período',
                    'D' => '7º período ou superior / já formado',
                ],
                'correct_answer' => null,
                'order' => 1,
            ],

            // ── Perguntas Técnicas ───────────────────────────────────────
            [
                'type' => 'technical',
                'content' => 'Qual padrão arquitetural separa a aplicação em Model, View e Controller?',
                'options' => [
                    'A' => 'Singleton',
                    'B' => 'MVC',
                    'C' => 'Observer',
                    'D' => 'Factory',
                ],
                'correct_answer' => 'B',
                'order' => 2,
            ],
            [
                'type' => 'technical',
                'content' => 'Em um banco de dados relacional, qual comando SQL é utilizado para buscar registros em uma tabela?',
                'options' => [
                    'A' => 'INSERT',
                    'B' => 'UPDATE',
                    'C' => 'SELECT',
                    'D' => 'DELETE',
                ],
                'correct_answer' => 'C',
                'order' => 3,
            ],
            [
                'type' => 'technical',
                'content' => 'Em uma API REST, qual método HTTP é semanticamente correto para criar um novo recurso?',
                'options' => [
                    'A' => 'GET',
                    'B' => 'PUT',
                    'C' => 'DELETE',
                    'D' => 'POST',
                ],
                'correct_answer' => 'D',
                'order' => 4,
            ],
            [
                'type' => 'technical',
                'content' => 'O que é uma chave estrangeira (Foreign Key) em um banco de dados relacional?',
                'options' => [
                    'A' => 'Um campo que armazena senhas criptografadas',
                    'B' => 'Um campo que referencia a chave primária de outra tabela, criando um relacionamento',
                    'C' => 'Um índice automático criado em todas as colunas',
                    'D' => 'Um campo que aceita apenas valores únicos',
                ],
                'correct_answer' => 'B',
                'order' => 5,
            ],
        ];

        foreach ($questions as $question) {
            Question::create($question);
        }
    }
}
