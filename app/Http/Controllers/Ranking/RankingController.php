<?php

namespace App\Http\Controllers\Ranking;

use App\Http\Controllers\Controller;
use App\Services\Ranking\Contracts\RankingServiceInterface;
use Illuminate\Http\JsonResponse;

class RankingController extends Controller
{
    public function __construct(
        private readonly RankingServiceInterface $rankingService,
    ) {}

    /**
     * Retorna o ranking global semanal (Top 20 por XP da semana).
     *
     * GET /api/ranking/global
     */
    public function global(): JsonResponse
    {
        $result = $this->rankingService->getWeeklyRanking();

        return response()->json($result);
    }
}
