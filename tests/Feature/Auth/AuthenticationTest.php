<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Auth - Register
|--------------------------------------------------------------------------
*/

describe('POST /api/auth/register', function () {

    it('registra um novo usuário com sucesso e retorna token', function () {
        $payload = [
            'name' => 'João Silva',
            'email' => 'joao@codequest.dev',
            'password' => 'Senh@Segura123',
            'password_confirmation' => 'Senh@Segura123',
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'xp_total', 'nivel', 'stack_interesse'],
                    'token',
                ],
                'message',
            ])
            ->assertJsonPath('data.user.name', 'João Silva')
            ->assertJsonPath('data.user.email', 'joao@codequest.dev')
            ->assertJsonPath('data.user.xp_total', 0)
            ->assertJsonPath('data.user.nivel', 1)
            ->assertJsonPath('message', 'Registro realizado com sucesso.');

        $this->assertDatabaseHas('users', [
            'email' => 'joao@codequest.dev',
        ]);
    });

    it('falha ao registrar com e-mail já existente', function () {
        User::factory()->create(['email' => 'duplicado@codequest.dev']);

        $payload = [
            'name' => 'Outro Usuário',
            'email' => 'duplicado@codequest.dev',
            'password' => 'Senh@Segura123',
            'password_confirmation' => 'Senh@Segura123',
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('falha ao registrar sem campos obrigatórios', function () {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    it('falha ao registrar com senha fraca (menos de 8 caracteres)', function () {
        $payload = [
            'name' => 'Usuário Teste',
            'email' => 'teste@codequest.dev',
            'password' => '123',
            'password_confirmation' => '123',
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('falha ao registrar quando confirmação de senha não confere', function () {
        $payload = [
            'name' => 'Usuário Teste',
            'email' => 'teste@codequest.dev',
            'password' => 'Senh@Segura123',
            'password_confirmation' => 'SenhaDiferente',
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('falha ao registrar com e-mail em formato inválido', function () {
        $payload = [
            'name' => 'Usuário Teste',
            'email' => 'email-invalido',
            'password' => 'Senh@Segura123',
            'password_confirmation' => 'Senh@Segura123',
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });
});

/*
|--------------------------------------------------------------------------
| Auth - Login
|--------------------------------------------------------------------------
*/

describe('POST /api/auth/login', function () {

    it('realiza login com credenciais corretas e retorna token', function () {
        $user = User::factory()->create([
            'email' => 'joao@codequest.dev',
            'password' => bcrypt('Senh@Segura123'),
        ]);

        $payload = [
            'email' => 'joao@codequest.dev',
            'password' => 'Senh@Segura123',
        ];

        $response = $this->postJson('/api/auth/login', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'xp_total', 'nivel', 'stack_interesse'],
                    'token',
                ],
                'message',
            ])
            ->assertJsonPath('data.user.email', 'joao@codequest.dev')
            ->assertJsonPath('message', 'Login realizado com sucesso.');
    });

    it('falha ao fazer login com senha incorreta', function () {
        User::factory()->create([
            'email' => 'joao@codequest.dev',
            'password' => bcrypt('Senh@Segura123'),
        ]);

        $payload = [
            'email' => 'joao@codequest.dev',
            'password' => 'SenhaErrada',
        ];

        $response = $this->postJson('/api/auth/login', $payload);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Credenciais inválidas.');
    });

    it('falha ao fazer login com e-mail inexistente', function () {
        $payload = [
            'email' => 'naoexiste@codequest.dev',
            'password' => 'Senh@Segura123',
        ];

        $response = $this->postJson('/api/auth/login', $payload);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Credenciais inválidas.');
    });

    it('falha ao fazer login sem campos obrigatórios', function () {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    });
});
