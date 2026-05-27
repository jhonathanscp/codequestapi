<?php

namespace App\Services\Ranking\Contracts;

/**
 * Contrato para o serviço de ranking semanal.
 */
interface RankingServiceInterface
{
    /**
     * Retorna o ranking dos top N usuários baseado no XP da semana atual.
     *
     * @param int $limit Quantidade máxima de posições no ranking.
     * @return array{data: array<int, array>, meta: array{semana_inicio: string, semana_fim: string}}
     */
    public function getWeeklyRanking(int $limit = 20): array;
}
