<?php

use App\Models\TutorMessage;
use App\Models\User;
use App\Services\Ai\AiOrchestratorService;

/*
|--------------------------------------------------------------------------
| Tutor IA - GET /api/tutor/chat
|--------------------------------------------------------------------------
*/

describe('GET /api/tutor/chat', function () {

    it('retorna o histórico de mensagens do usuário autenticado', function () {
        $user = User::factory()->create();

        TutorMessage::factory()->fromUser()->create([
            'user_id' => $user->id,
            'content' => 'O que é uma árvore binária?',
            'created_at' => now()->subMinutes(3),
        ]);
        TutorMessage::factory()->fromAssistant()->create([
            'user_id' => $user->id,
            'content' => 'Uma árvore binária é uma estrutura de dados hierárquica...',
            'created_at' => now()->subMinutes(2),
        ]);
        TutorMessage::factory()->fromUser()->create([
            'user_id' => $user->id,
            'content' => 'Como implemento uma busca nela?',
            'created_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/tutor/chat');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'role', 'content', 'roadmap_node_id', 'created_at'],
                ],
            ]);
    });

    it('retorna as mensagens em ordem cronológica (mais antiga primeiro)', function () {
        $user = User::factory()->create();

        TutorMessage::factory()->fromUser()->create([
            'user_id' => $user->id,
            'content' => 'Primeira mensagem',
            'created_at' => now()->subMinutes(10),
        ]);
        TutorMessage::factory()->fromAssistant()->create([
            'user_id' => $user->id,
            'content' => 'Segunda mensagem',
            'created_at' => now()->subMinutes(5),
        ]);
        TutorMessage::factory()->fromUser()->create([
            'user_id' => $user->id,
            'content' => 'Terceira mensagem',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/tutor/chat');

        $contents = collect($response->json('data'))->pluck('content')->toArray();
        expect($contents)->toBe(['Primeira mensagem', 'Segunda mensagem', 'Terceira mensagem']);
    });

    it('não retorna mensagens de outros usuários', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        TutorMessage::factory()->fromUser()->create([
            'user_id' => $user->id,
            'content' => 'Minha dúvida',
        ]);
        TutorMessage::factory()->fromUser()->create([
            'user_id' => $otherUser->id,
            'content' => 'Dúvida de outro aluno',
        ]);

        $response = $this->actingAs($user)->getJson('/api/tutor/chat');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.content', 'Minha dúvida');
    });

    it('retorna array vazio quando não há histórico', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/tutor/chat');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    });

    it('retorna 401 para usuário não autenticado', function () {
        $response = $this->getJson('/api/tutor/chat');

        $response->assertStatus(401);
    });
});

/*
|--------------------------------------------------------------------------
| Tutor IA - POST /api/tutor/message
|--------------------------------------------------------------------------
*/

describe('POST /api/tutor/message', function () {

    it('rejeita payload sem campo message', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/tutor/message', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    });

    it('rejeita mensagem vazia', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/tutor/message', [
            'message' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    });

    it('rejeita roadmap_node_id inválido (não inteiro)', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/tutor/message', [
            'message' => 'Dúvida qualquer',
            'roadmap_node_id' => 'abc',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['roadmap_node_id']);
    });

    it('processa mensagem com sucesso, chama a IA e salva ambas as mensagens', function () {
        $user = User::factory()->create();

        $fakeResponse = "Ótima pergunta! Vamos pensar juntos:\n\n"
            . "1. O que você já sabe sobre estruturas lineares?\n"
            . "2. Uma pilha tem uma regra especial de acesso. Qual seria?\n\n"
            . "Tente refletir sobre isso e me diga o que acha!";

        $mockOrchestrator = Mockery::mock(AiOrchestratorService::class);
        $mockOrchestrator->shouldReceive('conversarComTutor')
            ->once()
            ->withArgs(function (string $mensagem, array $historico) {
                return $mensagem === 'O que é uma pilha (stack)?'
                    && $historico === [];
            })
            ->andReturn($fakeResponse);

        $this->app->instance(AiOrchestratorService::class, $mockOrchestrator);

        $response = $this->actingAs($user)->postJson('/api/tutor/message', [
            'message' => 'O que é uma pilha (stack)?',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'role', 'content', 'roadmap_node_id', 'created_at'],
            ])
            ->assertJsonPath('data.role', 'assistant')
            ->assertJsonPath('data.content', $fakeResponse);

        // Verifica que DUAS mensagens foram salvas (user + assistant)
        expect(TutorMessage::where('user_id', $user->id)->count())->toBe(2);

        // Verifica a mensagem do aluno
        $this->assertDatabaseHas('tutor_messages', [
            'user_id' => $user->id,
            'role' => 'user',
            'content' => 'O que é uma pilha (stack)?',
        ]);

        // Verifica a resposta do tutor
        $this->assertDatabaseHas('tutor_messages', [
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $fakeResponse,
        ]);
    });

    it('envia o histórico prévio como contexto (memory) para a IA', function () {
        $user = User::factory()->create();

        // Histórico pré-existente
        TutorMessage::factory()->fromUser()->create([
            'user_id' => $user->id,
            'content' => 'O que é recursão?',
            'created_at' => now()->subMinutes(5),
        ]);
        TutorMessage::factory()->fromAssistant()->create([
            'user_id' => $user->id,
            'content' => 'Recursão é quando uma função chama a si mesma.',
            'created_at' => now()->subMinutes(4),
        ]);

        $mockOrchestrator = Mockery::mock(AiOrchestratorService::class);
        $mockOrchestrator->shouldReceive('conversarComTutor')
            ->once()
            ->withArgs(function (string $mensagem, array $historico) {
                // Deve receber o histórico completo com 2 mensagens
                return $mensagem === 'Me dê um exemplo prático'
                    && count($historico) === 2
                    && $historico[0]['role'] === 'user'
                    && $historico[0]['content'] === 'O que é recursão?'
                    && $historico[1]['role'] === 'assistant'
                    && $historico[1]['content'] === 'Recursão é quando uma função chama a si mesma.';
            })
            ->andReturn('Claro! Pense na sequência de Fibonacci...');

        $this->app->instance(AiOrchestratorService::class, $mockOrchestrator);

        $response = $this->actingAs($user)->postJson('/api/tutor/message', [
            'message' => 'Me dê um exemplo prático',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.content', 'Claro! Pense na sequência de Fibonacci...');

        // 2 do histórico + 2 novas (user + assistant) = 4
        expect(TutorMessage::where('user_id', $user->id)->count())->toBe(4);
    });

    it('aceita roadmap_node_id opcional e o persiste na mensagem', function () {
        $user = User::factory()->create();

        $mockOrchestrator = Mockery::mock(AiOrchestratorService::class);
        $mockOrchestrator->shouldReceive('conversarComTutor')
            ->once()
            ->andReturn('Vamos analisar este desafio...');

        $this->app->instance(AiOrchestratorService::class, $mockOrchestrator);

        $response = $this->actingAs($user)->postJson('/api/tutor/message', [
            'message' => 'Não entendo este desafio',
            'roadmap_node_id' => 42,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('tutor_messages', [
            'user_id' => $user->id,
            'role' => 'user',
            'content' => 'Não entendo este desafio',
            'roadmap_node_id' => 42,
        ]);

        $this->assertDatabaseHas('tutor_messages', [
            'user_id' => $user->id,
            'role' => 'assistant',
            'roadmap_node_id' => 42,
        ]);
    });

    it('retorna 401 para usuário não autenticado', function () {
        $response = $this->postJson('/api/tutor/message', [
            'message' => 'Qualquer dúvida',
        ]);

        $response->assertStatus(401);
    });
});
