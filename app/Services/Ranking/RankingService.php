<?php

namespace App\Services\Ranking;

use App\Services\Ranking\Contracts\RankingServiceInterface;
use Illuminate\Support\Facades\DB;

class RankingService implements RankingServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function getWeeklyRanking(int $limit = 20): array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $ranking = DB::table('xp_logs')
            ->join('users', 'xp_logs.user_id', '=', 'users.id')
            ->where('xp_logs.created_at', '>=', $startOfWeek)
            ->where('xp_logs.created_at', '<=', $endOfWeek)
            ->groupBy('xp_logs.user_id', 'users.name', 'users.nivel')
            ->select([
                'xp_logs.user_id',
                'users.name',
                'users.nivel',
                DB::raw('SUM(xp_logs.amount) as xp_semanal'),
            ])
            ->orderByDesc('xp_semanal')
            ->limit($limit)
            ->get();

        $data = $ranking->values()->map(function ($row, int $index) {
            return [
                'position' => $index + 1,
                'user_id' => $row->user_id,
                'name' => $row->name,
                'nivel' => $row->nivel,
                'xp_semanal' => (int) $row->xp_semanal,
            ];
        })->toArray();

        return [
            'data' => $data,
            'meta' => [
                'semana_inicio' => $startOfWeek->toISOString(),
                'semana_fim' => $endOfWeek->toISOString(),
            ],
        ];
    }
}
