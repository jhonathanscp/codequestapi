<?php

namespace App\Services\Tutor\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para o serviço de chat com o Tutor IA.
 */
interface TutorServiceInterface
{
    /**
     * Retorna o histórico de mensagens do usuário ordenado cronologicamente.
     *
     * @return Collection<int, \App\Models\TutorMessage>
     */
    public function getChatHistory(User $user): Collection;

    /**
     * Processa a mensagem do aluno: salva, injeta histórico, chama a IA e persiste a resposta.
     *
     * @param User $user
     * @param string $message Mensagem/dúvida do aluno.
     * @param int|null $roadmapNodeId ID do nó do roadmap para contexto (opcional).
     * @return \App\Models\TutorMessage A mensagem de resposta do tutor IA.
     */
    public function sendMessage(User $user, string $message, ?int $roadmapNodeId = null): \App\Models\TutorMessage;
}
