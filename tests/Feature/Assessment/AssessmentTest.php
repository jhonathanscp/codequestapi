<?php

use App\Models\Question;
use App\Models\Roadmap;
use App\Models\User;
use App\Models\UserAnswer;
use App\Services\Ai\AiOrchestratorService;

/*
|--------------------------------------------------------------------------
| Assessment - GET /api/assessment/questions
|--------------------------------------------------------------------------
*/

describe('GET /api/assessment/questions', function () {

    it('retorna as questões de nivelamento para um usuário autenticado', function () {
        $user = User::factory()->create();

        // Criar 1 pergunta de perfil + 4 técnicas (simulando o seeder)
        Question::factory()->profile()->create([
            'content' => 'Em qual período você está?',
            'options' => ['A' => '1º', 'B' => '3º', 'C' => '5º', 'D' => '7º+'],
            'order' => 1,
        ]);
        Question::factory()->technical()->count(4)->sequence(
            ['order' => 2, 'correct_answer' => 'B'],
            ['order' => 3, 'correct_answer' => 'C'],
            ['order' => 4, 'correct_answer' => 'D'],
            ['order' => 5, 'correct_answer' => 'B'],
        )->create();

        $response = $this->actingAs($user)->getJson('/api/assessment/questions');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'content', 'options', 'order'],
                ],
            ]);
    });

    it('não expõe o campo correct_answer no JSON da resposta', function () {
        $user = User::factory()->create();

        Question::factory()->technical()->create([
            'correct_answer' => 'A',
        ]);

        $response = $this->actingAs($user)->getJson('/api/assessment/questions');

        $response->assertStatus(200);

        // Verifica que nenhuma questão retornada contém correct_answer
        $questions = $response->json('data');
        foreach ($questions as $question) {
            expect($question)->not->toHaveKey('correct_answer');
        }
    });

    it('retorna as questões ordenadas pelo campo order', function () {
        $user = User::factory()->create();

        Question::factory()->create(['order' => 3, 'content' => 'Terceira']);
        Question::factory()->create(['order' => 1, 'content' => 'Primeira']);
        Question::factory()->create(['order' => 2, 'content' => 'Segunda']);

        $response = $this->actingAs($user)->getJson('/api/assessment/questions');

        $response->assertStatus(200);

        $contents = collect($response->json('data'))->pluck('content')->toArray();
        expect($contents)->toBe(['Primeira', 'Segunda', 'Terceira']);
    });

    it('retorna 401 para usuário não autenticado', function () {
        $response = $this->getJson('/api/assessment/questions');

        $response->assertStatus(401);
    });
});

/*
|--------------------------------------------------------------------------
| Assessment - POST /api/assessment/submit
|--------------------------------------------------------------------------
*/

describe('POST /api/assessment/submit', function () {

    it('rejeita payload sem campo answers', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/assessment/submit', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['answers']);
    });

    it('rejeita payload com answers em formato inválido', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/assessment/submit', [
            'answers' => 'isso não é um array',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['answers']);
    });

    it('rejeita quando question_id de um answer não existe', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/assessment/submit', [
            'answers' => [
                ['question_id' => 99999, 'answer' => 'A'],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['answers.0.question_id']);
    });

    it('rejeita quando falta o campo answer em um item', function () {
        $user = User::factory()->create();
        $question = Question::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/assessment/submit', [
            'answers' => [
                ['question_id' => $question->id],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['answers.0.answer']);
    });

    it('processa submissão válida, calcula pontuação e gera trilha via IA', function () {
        $user = User::factory()->create(['stack_interesse' => ['PHP', 'Laravel']]);

        // Criar questões
        $profileQuestion = Question::factory()->profile()->create([
            'content' => 'Em qual período você está?',
            'options' => ['A' => '1º', 'B' => '3º', 'C' => '5º', 'D' => '7º+'],
            'order' => 1,
        ]);
        $techQuestion1 = Question::factory()->technical()->create(['correct_answer' => 'B', 'order' => 2]);
        $techQuestion2 = Question::factory()->technical()->create(['correct_answer' => 'C', 'order' => 3]);
        $techQuestion3 = Question::factory()->technical()->create(['correct_answer' => 'D', 'order' => 4]);
        $techQuestion4 = Question::factory()->technical()->create(['correct_answer' => 'A', 'order' => 5]);

        // JSON simulado que a IA retornaria
        $fakeTrilhaJson = json_encode([
            'trilha' => [
                'titulo' => 'Trilha Backend — Nível Intermediário',
                'modulos' => [
                    [
                        'id' => 1,
                        'titulo' => 'Fundamentos de PHP',
                        'descricao' => 'Revisão dos conceitos básicos de PHP',
                        'nos' => [
                            ['id' => 1, 'titulo' => 'Variáveis e Tipos', 'tipo' => 'teoria', 'xp' => 50],
                            ['id' => 2, 'titulo' => 'Desafio: Funções', 'tipo' => 'desafio', 'xp' => 100],
                        ],
                    ],
                ],
            ],
        ]);

        // Mock do AiOrchestratorService — NENHUMA chamada real à API
        $mockOrchestrator = Mockery::mock(AiOrchestratorService::class);
        $mockOrchestrator->shouldReceive('construirTrilha')
            ->once()
            ->withArgs(function (string $nivel, array $interesses, array $contexto) {
                return in_array($nivel, ['iniciante', 'intermediario', 'avancado'])
                    && $interesses === ['PHP', 'Laravel']
                    && isset($contexto['acertos'])
                    && isset($contexto['total_tecnicas'])
                    && isset($contexto['periodo_curso']);
            })
            ->andReturn($fakeTrilhaJson);

        $this->app->instance(AiOrchestratorService::class, $mockOrchestrator);

        // Submeter respostas — 3 de 4 técnicas corretas
        $payload = [
            'answers' => [
                ['question_id' => $profileQuestion->id, 'answer' => 'B'], // 3º período
                ['question_id' => $techQuestion1->id, 'answer' => 'B'],   // ✓
                ['question_id' => $techQuestion2->id, 'answer' => 'C'],   // ✓
                ['question_id' => $techQuestion3->id, 'answer' => 'D'],   // ✓
                ['question_id' => $techQuestion4->id, 'answer' => 'B'],   // ✗
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/assessment/submit', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'pontuacao_tecnica',
                    'nivel_calculado',
                    'roadmap',
                ],
            ])
            ->assertJsonPath('data.pontuacao_tecnica', 3)
            ->assertJsonPath('message', 'Nivelamento concluído com sucesso.');

        // Verifica que as respostas foram salvas no banco
        expect(UserAnswer::where('user_id', $user->id)->count())->toBe(5);

        // Verifica que o roadmap foi salvo no banco
        $this->assertDatabaseHas('roadmaps', [
            'user_id' => $user->id,
            'pontuacao_tecnica' => 3,
        ]);
    });

    it('calcula nível iniciante quando acerta 0 ou 1 questão técnica', function () {
        $user = User::factory()->create(['stack_interesse' => ['Python']]);

        $profileQ = Question::factory()->profile()->create(['order' => 1]);
        $techQ1 = Question::factory()->technical()->create(['correct_answer' => 'A', 'order' => 2]);
        $techQ2 = Question::factory()->technical()->create(['correct_answer' => 'B', 'order' => 3]);
        $techQ3 = Question::factory()->technical()->create(['correct_answer' => 'C', 'order' => 4]);
        $techQ4 = Question::factory()->technical()->create(['correct_answer' => 'D', 'order' => 5]);

        $fakeTrilha = json_encode(['trilha' => ['titulo' => 'Trilha Iniciante', 'modulos' => []]]);

        $mockOrchestrator = Mockery::mock(AiOrchestratorService::class);
        $mockOrchestrator->shouldReceive('construirTrilha')
            ->once()
            ->withArgs(fn (string $nivel) => $nivel === 'iniciante')
            ->andReturn($fakeTrilha);

        $this->app->instance(AiOrchestratorService::class, $mockOrchestrator);

        // 0 de 4 corretas
        $payload = [
            'answers' => [
                ['question_id' => $profileQ->id, 'answer' => 'A'],
                ['question_id' => $techQ1->id, 'answer' => 'D'],  // ✗
                ['question_id' => $techQ2->id, 'answer' => 'D'],  // ✗
                ['question_id' => $techQ3->id, 'answer' => 'D'],  // ✗
                ['question_id' => $techQ4->id, 'answer' => 'A'],  // ✗
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/assessment/submit', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.nivel_calculado', 'iniciante')
            ->assertJsonPath('data.pontuacao_tecnica', 0);
    });

    it('calcula nível avançado quando acerta todas as questões técnicas', function () {
        $user = User::factory()->create(['stack_interesse' => ['Go']]);

        $profileQ = Question::factory()->profile()->create(['order' => 1]);
        $techQ1 = Question::factory()->technical()->create(['correct_answer' => 'A', 'order' => 2]);
        $techQ2 = Question::factory()->technical()->create(['correct_answer' => 'B', 'order' => 3]);
        $techQ3 = Question::factory()->technical()->create(['correct_answer' => 'C', 'order' => 4]);
        $techQ4 = Question::factory()->technical()->create(['correct_answer' => 'D', 'order' => 5]);

        $fakeTrilha = json_encode(['trilha' => ['titulo' => 'Trilha Avançada', 'modulos' => []]]);

        $mockOrchestrator = Mockery::mock(AiOrchestratorService::class);
        $mockOrchestrator->shouldReceive('construirTrilha')
            ->once()
            ->withArgs(fn (string $nivel) => $nivel === 'avancado')
            ->andReturn($fakeTrilha);

        $this->app->instance(AiOrchestratorService::class, $mockOrchestrator);

        // 4 de 4 corretas
        $payload = [
            'answers' => [
                ['question_id' => $profileQ->id, 'answer' => 'D'],
                ['question_id' => $techQ1->id, 'answer' => 'A'],  // ✓
                ['question_id' => $techQ2->id, 'answer' => 'B'],  // ✓
                ['question_id' => $techQ3->id, 'answer' => 'C'],  // ✓
                ['question_id' => $techQ4->id, 'answer' => 'D'],  // ✓
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/assessment/submit', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('data.nivel_calculado', 'avancado')
            ->assertJsonPath('data.pontuacao_tecnica', 4);
    });

    it('retorna 401 para usuário não autenticado', function () {
        $response = $this->postJson('/api/assessment/submit', [
            'answers' => [],
        ]);

        $response->assertStatus(401);
    });
});
