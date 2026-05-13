<?php

namespace App\Services\Auth\Contracts;

use App\Models\User;

interface AuthServiceInterface
{
    /**
     * Registra um novo usuário e gera um token de acesso.
     *
     * @param array<string, mixed> $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array;

    /**
     * Autentica um usuário existente e gera um token de acesso.
     *
     * @param array<string, mixed> $credentials
     * @return array{user: User, token: string}|null Retorna null se as credenciais forem inválidas
     */
    public function login(array $credentials): ?array;
}
