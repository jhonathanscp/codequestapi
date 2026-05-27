<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assessment\SubmitAssessmentRequest;
use App\Services\Assessment\Contracts\AssessmentServiceInterface;
use Illuminate\Http\JsonResponse;

class AssessmentController extends Controller
{
    public function __construct(
        private readonly AssessmentServiceInterface $assessmentService,
    ) {}

    /**
     * Retorna as questões de nivelamento do banco de dados.
     *
     * GET /api/assessment/questions
     */
    public function index(): JsonResponse
    {
        $questions = $this->assessmentService->getQuestions();

        // Remove correct_answer do JSON de resposta
        $data = $questions->map(function ($question) {
            return [
                'id' => $question->id,
                'type' => $question->type,
                'content' => $question->content,
                'options' => $question->options,
                'order' => $question->order,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Recebe as respostas do aluno, calcula pontuação e gera trilha via IA.
     *
     * POST /api/assessment/submit
     */
    public function submit(SubmitAssessmentRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $result = $this->assessmentService->submitAnswers(
            $user,
            $request->validated('answers'),
        );

        return response()->json([
            'message' => 'Nivelamento concluído com sucesso.',
            'data' => [
                'pontuacao_tecnica' => $result['pontuacao_tecnica'],
                'nivel_calculado' => $result['nivel_calculado'],
                'roadmap' => $result['roadmap'],
            ],
        ]);
    }
}
