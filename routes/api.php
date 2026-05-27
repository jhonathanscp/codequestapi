<?php

use App\Http\Controllers\Assessment\AssessmentController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Ranking\RankingController;
use App\Http\Controllers\Tutor\TutorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - CODE QUEST
|--------------------------------------------------------------------------
|
| Rotas públicas de autenticação e rotas protegidas por Sanctum.
|
*/

// ── Autenticação (Público) ──────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// ── Rotas Protegidas (Sanctum) ──────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Perfil do Usuário
    Route::get('/user/profile', [ProfileController::class, 'show']);
    Route::put('/user/profile', [ProfileController::class, 'update']);

    // Nivelamento / Assessment
    Route::prefix('assessment')->group(function () {
        Route::get('/questions', [AssessmentController::class, 'index']);
        Route::post('/submit', [AssessmentController::class, 'submit']);
    });

    // Tutor IA Tech
    Route::prefix('tutor')->group(function () {
        Route::get('/chat', [TutorController::class, 'index']);
        Route::post('/message', [TutorController::class, 'sendMessage']);
    });

    // Ranking / Leaderboard
    Route::prefix('ranking')->group(function () {
        Route::get('/global', [RankingController::class, 'global']);
    });
});

