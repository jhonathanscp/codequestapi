<?php

namespace App\Services\Assessment\Contracts;

/**
 * Contrato para o serviço de nivelamento/assessment.
 */
interface AssessmentServiceInterface
{
    /**
     * Retorna todas as questões de nivelamento ordenadas.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Question>
     */
    public function getQuestions(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Processa as respostas do aluno, calcula pontuação e gera trilha via IA.
     *
     * @param \App\Models\User $user
     * @param array<int, array{question_id: int, answer: string}> $answers
     * @return array{pontuacao_tecnica: int, nivel_calculado: string, roadmap: \App\Models\Roadmap}
     */
    public function submitAnswers(\App\Models\User $user, array $answers): array;
}
