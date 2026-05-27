<?php

namespace App\Providers;

use App\Services\Ai\AiOrchestratorService;
use App\Services\Ai\Contracts\LlmServiceInterface;
use App\Services\Assessment\AssessmentService;
use App\Services\Assessment\Contracts\AssessmentServiceInterface;
use App\Services\Auth\AuthService;
use App\Services\Auth\Contracts\AuthServiceInterface;
use App\Services\Ranking\Contracts\RankingServiceInterface;
use App\Services\Ranking\RankingService;
use App\Services\Tutor\Contracts\TutorServiceInterface;
use App\Services\Tutor\TutorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);

        // ── AI / LLM ────────────────────────────────────────────────────
        $this->app->singleton(AiOrchestratorService::class);

        $this->app->bind(LlmServiceInterface::class, function () {
            return AiOrchestratorService::resolveDriver();
        });

        // ── Assessment ──────────────────────────────────────────────────
        $this->app->bind(AssessmentServiceInterface::class, AssessmentService::class);

        // ── Tutor ───────────────────────────────────────────────────────
        $this->app->bind(TutorServiceInterface::class, TutorService::class);

        // ── Ranking ─────────────────────────────────────────────────────
        $this->app->bind(RankingServiceInterface::class, RankingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
