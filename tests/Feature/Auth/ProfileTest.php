<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Profile - Show
|--------------------------------------------------------------------------
*/

describe('GET /api/user/profile', function () {

    it('retorna o perfil do usuário autenticado', function () {
        $user = User::factory()->create([
            'name' => 'Maria Dev',
            'email' => 'maria@codequest.dev',
            'xp_total' => 150,
            'nivel' => 3,
            'stack_interesse' => ['PHP', 'Laravel', 'PostgreSQL'],
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'email', 'xp_total', 'nivel', 'stack_interesse',
                ],
            ])
            ->assertJsonPath('data.name', 'Maria Dev')
            ->assertJsonPath('data.email', 'maria@codequest.dev')
            ->assertJsonPath('data.xp_total', 150)
            ->assertJsonPath('data.nivel', 3)
            ->assertJsonPath('data.stack_interesse', ['PHP', 'Laravel', 'PostgreSQL']);
    });

    it('retorna 401 quando não autenticado', function () {
        $response = $this->getJson('/api/user/profile');

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Unauthenticated.');
    });
});

/*
|--------------------------------------------------------------------------
| Profile - Update
|--------------------------------------------------------------------------
*/

describe('PUT /api/user/profile', function () {

    it('atualiza o nome do usuário autenticado', function () {
        $user = User::factory()->create(['name' => 'Nome Antigo']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/profile', [
                'name' => 'Nome Novo',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nome Novo')
            ->assertJsonPath('message', 'Perfil atualizado com sucesso.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nome Novo',
        ]);
    });

    it('atualiza a stack de interesse do usuário', function () {
        $user = User::factory()->create(['stack_interesse' => null]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/profile', [
                'stack_interesse' => ['React', 'TypeScript', 'Node.js'],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.stack_interesse', ['React', 'TypeScript', 'Node.js'])
            ->assertJsonPath('message', 'Perfil atualizado com sucesso.');
    });

    it('atualiza múltiplos campos simultaneamente', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/profile', [
                'name' => 'Dev Atualizado',
                'stack_interesse' => ['Python', 'Django'],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Dev Atualizado')
            ->assertJsonPath('data.stack_interesse', ['Python', 'Django']);
    });

    it('não permite alterar o e-mail para um já existente', function () {
        User::factory()->create(['email' => 'existente@codequest.dev']);
        $user = User::factory()->create(['email' => 'original@codequest.dev']);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/profile', [
                'email' => 'existente@codequest.dev',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('não permite alterar xp_total ou nivel diretamente', function () {
        $user = User::factory()->create([
            'xp_total' => 0,
            'nivel' => 1,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/profile', [
                'xp_total' => 9999,
                'nivel' => 99,
            ]);

        $response->assertStatus(200);

        // Os campos de gamificação não devem ser alterados via endpoint de perfil
        $user->refresh();
        expect($user->xp_total)->toBe(0);
        expect($user->nivel)->toBe(1);
    });

    it('retorna 401 quando não autenticado', function () {
        $response = $this->putJson('/api/user/profile', [
            'name' => 'Tentativa',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('message', 'Unauthenticated.');
    });

    it('falha com stack_interesse em formato inválido', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/profile', [
                'stack_interesse' => 'nao-e-array',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['stack_interesse']);
    });
});
