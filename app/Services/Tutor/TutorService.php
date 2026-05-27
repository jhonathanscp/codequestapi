<?php

namespace App\Services\Tutor;

use App\Models\TutorMessage;
use App\Models\User;
use App\Services\Ai\AiOrchestratorService;
use App\Services\Tutor\Contracts\TutorServiceInterface;
use Illuminate\Database\Eloquent\Collection;

class TutorService implements TutorServiceInterface
{
    /**
     * Limite de mensagens do histórico injetadas como contexto (memory).
     * Evita payloads excessivamente grandes e mantém relevância.
     */
    private const int HISTORY_LIMIT = 20;

    public function __construct(
        private readonly AiOrchestratorService $aiOrchestrator,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getChatHistory(User $user): Collection
    {
        return TutorMessage::where('user_id', $user->id)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function sendMessage(User $user, string $message, ?int $roadmapNodeId = null): TutorMessage
    {
        // 1. Salvar a mensagem do aluno
        $userMessage = TutorMessage::create([
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $message,
            'roadmap_node_id' => $roadmapNodeId,
        ]);

        // 2. Buscar histórico recente para injetar como contexto (memory)
        $historico = TutorMessage::where('user_id', $user->id)
            ->where('id', '<', $userMessage->id) // Exclui a mensagem recém-criada
            ->orderBy('created_at')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->map(fn (TutorMessage $msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
            ])
            ->toArray();

        // 3. Chamar o AiOrchestratorService (Prompt P03)
        $aiResponse = $this->aiOrchestrator->conversarComTutor($message, $historico);

        // 4. Salvar a resposta do tutor IA
        $assistantMessage = TutorMessage::create([
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $aiResponse,
            'roadmap_node_id' => $roadmapNodeId,
        ]);

        return $assistantMessage;
    }
}
