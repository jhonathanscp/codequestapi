<?php

namespace App\Services\Assessment;

use App\Models\Question;
use App\Models\Roadmap;
use App\Models\User;
use App\Models\UserAnswer;
use App\Services\Ai\AiOrchestratorService;
use App\Services\Assessment\Contracts\AssessmentServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class AssessmentService implements AssessmentServiceInterface
{
    public function __construct(
        private readonly AiOrchestratorService $aiOrchestrator,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getQuestions(): Collection
    {
        return Question::orderBy('order')->get();
    }

    /**
     * {@inheritDoc}
     */
    public function submitAnswers(User $user, array $answers): array
    {
        // 1. Carregar todas as questões referenciadas
        $questionIds = collect($answers)->pluck('question_id')->toArray();
        $questions = Question::whereIn('id', $questionIds)->get()->keyBy('id');

        $acertos = 0;
        $totalTecnicas = 0;
        $periodoCurso = null;

        // 2. Processar e salvar cada resposta
        foreach ($answers as $answerData) {
            $question = $questions->get($answerData['question_id']);

            $isCorrect = null;

            if ($question->isTechnical()) {
                $totalTecnicas++;
                $isCorrect = $question->correct_answer === $answerData['answer'];
                if ($isCorrect) {
                    $acertos++;
                }
            }

            if ($question->isProfile()) {
                $periodoCurso = $answerData['answer'];
            }

            UserAnswer::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'question_id' => $answerData['question_id'],
                ],
                [
                    'answer' => $answerData['answer'],
                    'is_correct' => $isCorrect,
                ],
            );
        }

        // 3. Calcular nível baseado na pontuação
        $nivelCalculado = $this->calcularNivel($acertos, $totalTecnicas);

        // 4. Montar contexto (memory) para a IA
        $interesses = $user->stack_interesse ?? [];
        $contextoNivelamento = [
            'acertos' => $acertos,
            'total_tecnicas' => $totalTecnicas,
            'periodo_curso' => $periodoCurso,
        ];

        // 5. Chamar a IA para gerar a trilha (P02)
        $trilhaJson = $this->aiOrchestrator->construirTrilha(
            $nivelCalculado,
            $interesses,
            $contextoNivelamento,
        );

        // 6. Salvar o roadmap no banco
        $roadmap = Roadmap::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nivel_calculado' => $nivelCalculado,
                'pontuacao_tecnica' => $acertos,
                'trilha_json' => json_decode($trilhaJson, true) ?? [],
            ],
        );

        return [
            'pontuacao_tecnica' => $acertos,
            'nivel_calculado' => $nivelCalculado,
            'roadmap' => $roadmap,
        ];
    }

    /**
     * Calcula o nível do aluno com base na pontuação técnica.
     *
     * - 0–1 acertos: iniciante
     * - 2–3 acertos: intermediario
     * - 4 acertos:   avancado
     */
    private function calcularNivel(int $acertos, int $totalTecnicas): string
    {
        if ($totalTecnicas === 0) {
            return 'iniciante';
        }

        $percentual = $acertos / $totalTecnicas;

        return match (true) {
            $percentual >= 1.0 => 'avancado',
            $percentual >= 0.5 => 'intermediario',
            default => 'iniciante',
        };
    }
}
