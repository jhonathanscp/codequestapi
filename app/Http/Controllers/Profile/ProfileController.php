<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Retorna os dados do perfil do usuário autenticado.
     *
     * GET /api/user/profile
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user(),
        ]);
    }

    /**
     * Atualiza os dados do perfil do usuário autenticado.
     *
     * PUT /api/user/profile
     *
     * Nota: Os campos xp_total e nivel não são atualizáveis via este endpoint.
     * Esses valores são controlados exclusivamente pelo sistema de gamificação.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        // Apenas campos permitidos (name, email, stack_interesse) são atualizados.
        // xp_total e nivel são deliberadamente excluídos do FormRequest.
        $user->update($request->validated());

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
            'data' => $user->fresh(),
        ]);
    }
}
