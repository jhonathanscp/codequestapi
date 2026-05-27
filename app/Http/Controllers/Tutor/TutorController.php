<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tutor\SendMessageRequest;
use App\Services\Tutor\Contracts\TutorServiceInterface;
use Illuminate\Http\JsonResponse;

class TutorController extends Controller
{
    public function __construct(
        private readonly TutorServiceInterface $tutorService,
    ) {}

    /**
     * Retorna o histórico de mensagens do chat com o tutor.
     *
     * GET /api/tutor/chat
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = request()->user();

        $messages = $this->tutorService->getChatHistory($user);

        $data = $messages->map(fn ($msg) => [
            'id' => $msg->id,
            'role' => $msg->role,
            'content' => $msg->content,
            'roadmap_node_id' => $msg->roadmap_node_id,
            'created_at' => $msg->created_at->toISOString(),
        ]);

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Envia uma mensagem ao tutor e retorna a resposta da IA.
     *
     * POST /api/tutor/message
     */
    public function sendMessage(SendMessageRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $assistantMessage = $this->tutorService->sendMessage(
            $user,
            $request->validated('message'),
            $request->validated('roadmap_node_id'),
        );

        return response()->json([
            'message' => 'Resposta do tutor gerada com sucesso.',
            'data' => [
                'id' => $assistantMessage->id,
                'role' => $assistantMessage->role,
                'content' => $assistantMessage->content,
                'roadmap_node_id' => $assistantMessage->roadmap_node_id,
                'created_at' => $assistantMessage->created_at->toISOString(),
            ],
        ]);
    }
}
