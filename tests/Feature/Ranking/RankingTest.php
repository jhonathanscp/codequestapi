<?php

use App\Models\User;
use App\Models\XpLog;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Ranking Global - GET /api/ranking/global
|--------------------------------------------------------------------------
*/

describe('GET /api/ranking/global', function () {

    it('retorna status 200 e a estrutura JSON correta', function () {
        $user = User::factory()->create();

        XpLog::factory()->create([
            'user_id' => $user->id,
            'amount' => 100,
        ]);

        $response = $this->actingAs($user)->getJson('/api/ranking/global');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['position', 'user_id', 'name', 'nivel', 'xp_semanal'],
                ],
                'meta' => ['semana_inicio', 'semana_fim'],
            ]);
    });

    it('retorna os usuários ordenados por XP semanal decrescente', function () {
        // Congelar tempo: quarta-feira, 28 de maio de 2026, 15:00
        Carbon::setTestNow(Carbon::create(2026, 5, 28, 15, 0, 0));

        $user1 = User::factory()->create(['name' => 'Alice']);
        $user2 = User::factory()->create(['name' => 'Bob']);
        $user3 = User::factory()->create(['name' => 'Charlie']);

        // Alice: 150 XP esta semana
        XpLog::factory()->create(['user_id' => $user1->id, 'amount' => 100, 'created_at' => now()->subDay()]);
        XpLog::factory()->create(['user_id' => $user1->id, 'amount' => 50, 'created_at' => now()]);

        // Bob: 300 XP esta semana
        XpLog::factory()->create(['user_id' => $user2->id, 'amount' => 300, 'created_at' => now()]);

        // Charlie: 200 XP esta semana
        XpLog::factory()->create(['user_id' => $user3->id, 'amount' => 200, 'created_at' => now()]);

        $response = $this->actingAs($user1)->getJson('/api/ranking/global');

        $response->assertStatus(200);

        $ranking = $response->json('data');

        expect($ranking[0]['name'])->toBe('Bob')
            ->and($ranking[0]['xp_semanal'])->toBe(300)
            ->and($ranking[0]['position'])->toBe(1)
            ->and($ranking[1]['name'])->toBe('Charlie')
            ->and($ranking[1]['xp_semanal'])->toBe(200)
            ->and($ranking[1]['position'])->toBe(2)
            ->and($ranking[2]['name'])->toBe('Alice')
            ->and($ranking[2]['xp_semanal'])->toBe(150)
            ->and($ranking[2]['position'])->toBe(3);

        Carbon::setTestNow(); // Restaurar tempo real
    });

    it('NÃO contabiliza XP ganho na semana passada', function () {
        // Congelar tempo: segunda-feira, 25 de maio de 2026, 10:00
        Carbon::setTestNow(Carbon::create(2026, 5, 25, 10, 0, 0));

        $userThisWeek = User::factory()->create(['name' => 'Ativo']);
        $userLastWeek = User::factory()->create(['name' => 'Inativo']);

        // Ativo: 100 XP na segunda (esta semana)
        XpLog::factory()->create([
            'user_id' => $userThisWeek->id,
            'amount' => 100,
            'created_at' => now(),
        ]);

        // Inativo: 500 XP no domingo passado (semana anterior)
        XpLog::factory()->create([
            'user_id' => $userLastWeek->id,
            'amount' => 500,
            'created_at' => now()->subWeek(),
        ]);

        $response = $this->actingAs($userThisWeek)->getJson('/api/ranking/global');

        $response->assertStatus(200);

        $ranking = $response->json('data');

        // Apenas o Ativo deve aparecer no ranking
        $names = collect($ranking)->pluck('name')->toArray();
        expect($names)->toContain('Ativo')
            ->and($names)->not->toContain('Inativo');

        Carbon::setTestNow();
    });

    it('soma múltiplos XpLogs do mesmo usuário na semana', function () {
        Carbon::setTestNow(Carbon::create(2026, 5, 28, 12, 0, 0));

        $user = User::factory()->create(['name' => 'Estudioso']);

        XpLog::factory()->create(['user_id' => $user->id, 'amount' => 50, 'created_at' => now()->subDays(2)]);
        XpLog::factory()->create(['user_id' => $user->id, 'amount' => 75, 'created_at' => now()->subDay()]);
        XpLog::factory()->create(['user_id' => $user->id, 'amount' => 25, 'created_at' => now()]);

        $response = $this->actingAs($user)->getJson('/api/ranking/global');

        $response->assertStatus(200);
        expect($response->json('data.0.xp_semanal'))->toBe(150);

        Carbon::setTestNow();
    });

    it('limita o ranking a no máximo 20 posições', function () {
        Carbon::setTestNow(Carbon::create(2026, 5, 28, 12, 0, 0));

        $auth = User::factory()->create();

        // Criar 25 usuários com XP nesta semana
        for ($i = 1; $i <= 25; $i++) {
            $user = User::factory()->create();
            XpLog::factory()->create([
                'user_id' => $user->id,
                'amount' => $i * 10,
                'created_at' => now(),
            ]);
        }

        $response = $this->actingAs($auth)->getJson('/api/ranking/global');

        $response->assertStatus(200)
            ->assertJsonCount(20, 'data');

        Carbon::setTestNow();
    });

    it('retorna array vazio quando nenhum XP foi registrado na semana', function () {
        Carbon::setTestNow(Carbon::create(2026, 5, 28, 12, 0, 0));

        $user = User::factory()->create();

        // XP apenas na semana passada
        XpLog::factory()->create([
            'user_id' => $user->id,
            'amount' => 200,
            'created_at' => now()->subWeeks(2),
        ]);

        $response = $this->actingAs($user)->getJson('/api/ranking/global');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');

        Carbon::setTestNow();
    });

    it('retorna os metadados da semana corrente (inicio e fim)', function () {
        // Quarta-feira, 28 de maio 2026
        Carbon::setTestNow(Carbon::create(2026, 5, 28, 12, 0, 0));

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/ranking/global');

        $response->assertStatus(200);

        $meta = $response->json('meta');
        // Segunda-feira da semana: 25 de maio
        expect($meta['semana_inicio'])->toBe('2026-05-25T00:00:00.000000Z')
            ->and($meta['semana_fim'])->toBe('2026-05-31T23:59:59.999999Z');

        Carbon::setTestNow();
    });

    it('retorna 401 para usuário não autenticado', function () {
        $response = $this->getJson('/api/ranking/global');

        $response->assertStatus(401);
    });
});
