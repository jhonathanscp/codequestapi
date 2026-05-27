<?php

use App\Services\Ai\AiOrchestratorService;
use App\Services\Ai\Contracts\LlmServiceInterface;
use App\Services\Ai\Exceptions\AiProviderException;
use App\Services\Ai\PromptRegistry;

/*
|--------------------------------------------------------------------------
| Testes Unitários — AiOrchestratorService & PromptRegistry
|--------------------------------------------------------------------------
|
| Testa o serviço orquestrador usando um mock do LlmServiceInterface,
| garantindo que cada método de negócio (P01–P04) injeta os prompts
| e contextos corretos sem nenhuma chamada real à API.
|
*/

// ═══════════════════════════════════════════════════════════════════════════
//  PROMPT REGISTRY
// ═══════════════════════════════════════════════════════════════════════════

describe('PromptRegistry', function () {

    it('retorna todos os 4 prompts (P01–P04)', function () {
        $prompts = PromptRegistry::all();

        expect($prompts)
            ->toBeArray()
            ->toHaveKeys(['P01', 'P02', 'P03', 'P04'])
            ->and(count($prompts))->toBe(4);
    });

    it('retorna o prompt correto pelo ID', function () {
        $p01 = PromptRegistry::get('P01');

        expect($p01)
            ->toBeString()
            ->toContain('3 questões de múltipla escolha');
    });

    it('lança exceção para ID inexistente', function () {
        PromptRegistry::get('P99');
    })->throws(InvalidArgumentException::class, 'não encontrado');

    it('P01 contém instruções de formato JSON', function () {
        expect(PromptRegistry::get('P01'))->toContain('JSON');
    });

    it('P02 contém exemplo few-shot com trilha', function () {
        expect(PromptRegistry::get('P02'))
            ->toContain('trilha')
            ->toContain('modulos');
    });

    it('P03 proíbe fornecer código pronto', function () {
        expect(PromptRegistry::get('P03'))
            ->toContain('NUNCA forneça o código pronto');
    });

    it('P04 contém cadeia de verificação', function () {
        expect(PromptRegistry::get('P04'))
            ->toContain('Correção Lógica')
            ->toContain('Segurança')
            ->toContain('Eficiência');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
//  AI ORCHESTRATOR SERVICE — DRIVER RESOLUTION
// ═══════════════════════════════════════════════════════════════════════════

describe('AiOrchestratorService — resolveDriver', function () {

    it('resolve GeminiDriver quando AI_PROVIDER=gemini', function () {
        config(['services.ai.provider' => 'gemini']);
        config(['services.gemini.key' => 'test-key']);

        $driver = AiOrchestratorService::resolveDriver();

        expect($driver->getProviderName())->toBe('gemini');
    });

    it('resolve OpenAiDriver quando AI_PROVIDER=openai', function () {
        config(['services.ai.provider' => 'openai']);
        config(['services.openai.key' => 'test-key']);

        $driver = AiOrchestratorService::resolveDriver();

        expect($driver->getProviderName())->toBe('openai');
    });

    it('resolve DeepSeekDriver quando AI_PROVIDER=deepseek', function () {
        config(['services.ai.provider' => 'deepseek']);
        config(['services.deepseek.key' => 'test-key']);

        $driver = AiOrchestratorService::resolveDriver();

        expect($driver->getProviderName())->toBe('deepseek');
    });

    it('lança exceção para provedor não suportado', function () {
        config(['services.ai.provider' => 'claude']);

        AiOrchestratorService::resolveDriver();
    })->throws(InvalidArgumentException::class, 'não é suportado');
});

// ═══════════════════════════════════════════════════════════════════════════
//  AI ORCHESTRATOR SERVICE — MÉTODOS DE NEGÓCIO (P01–P04)
// ═══════════════════════════════════════════════════════════════════════════

describe('AiOrchestratorService — gerarNivelamento (P01)', function () {

    it('envia o prompt P01 com o tema correto', function () {
        $mockDriver = Mockery::mock(LlmServiceInterface::class);
        $mockDriver->shouldReceive('generateContent')
            ->once()
            ->withArgs(function (string $system, string $user, array $options) {
                return str_contains($system, '3 questões de múltipla escolha')
                    && str_contains($user, 'Estruturas de Dados')
                    && $options['temperature'] === 0.5;
            })
            ->andReturn('[{"id":1,"enunciado":"Pergunta 1"}]');

        $service = new AiOrchestratorService($mockDriver);
        $result = $service->gerarNivelamento('Estruturas de Dados');

        expect($result)->toContain('Pergunta 1');
    });
});

describe('AiOrchestratorService — construirTrilha (P02)', function () {

    it('envia o prompt P02 com nível e interesses', function () {
        $mockDriver = Mockery::mock(LlmServiceInterface::class);
        $mockDriver->shouldReceive('generateContent')
            ->once()
            ->withArgs(function (string $system, string $user, array $options) {
                return str_contains($system, 'construtor de trilhas')
                    && str_contains($user, 'intermediario')
                    && str_contains($user, 'PHP')
                    && str_contains($user, 'Laravel');
            })
            ->andReturn('{"trilha":{"titulo":"Trilha PHP"}}');

        $service = new AiOrchestratorService($mockDriver);
        $result = $service->construirTrilha('intermediario', ['PHP', 'Laravel']);

        expect($result)->toContain('Trilha PHP');
    });

    it('injeta contexto de nivelamento como memory', function () {
        $contexto = ['acertos' => 2, 'erros' => 1, 'nivel_estimado' => 'intermediario'];

        $mockDriver = Mockery::mock(LlmServiceInterface::class);
        $mockDriver->shouldReceive('generateContent')
            ->once()
            ->withArgs(function (string $system, string $user) use ($contexto) {
                return str_contains($user, 'Resultados do nivelamento')
                    && str_contains($user, json_encode($contexto, JSON_UNESCAPED_UNICODE));
            })
            ->andReturn('{"trilha":{}}');

        $service = new AiOrchestratorService($mockDriver);
        $service->construirTrilha('intermediario', ['PHP'], $contexto);
    });
});

describe('AiOrchestratorService — conversarComTutor (P03)', function () {

    it('envia o prompt P03 com a mensagem do aluno', function () {
        $mockDriver = Mockery::mock(LlmServiceInterface::class);
        $mockDriver->shouldReceive('generateContent')
            ->once()
            ->withArgs(function (string $system, string $user) {
                return str_contains($system, 'NUNCA forneça o código pronto')
                    && str_contains($user, 'O que é uma pilha?');
            })
            ->andReturn('Uma pilha é uma estrutura de dados do tipo LIFO...');

        $service = new AiOrchestratorService($mockDriver);
        $result = $service->conversarComTutor('O que é uma pilha?');

        expect($result)->toContain('LIFO');
    });

    it('injeta histórico da conversa como memory', function () {
        $historico = [
            ['role' => 'user', 'content' => 'O que é recursão?'],
            ['role' => 'assistant', 'content' => 'Recursão é quando uma função chama a si mesma.'],
        ];

        $mockDriver = Mockery::mock(LlmServiceInterface::class);
        $mockDriver->shouldReceive('generateContent')
            ->once()
            ->withArgs(function (string $system, string $user) {
                return str_contains($user, 'Histórico da conversa')
                    && str_contains($user, '[Aluno]: O que é recursão?')
                    && str_contains($user, '[Tutor]: Recursão é quando')
                    && str_contains($user, 'Me dê um exemplo');
            })
            ->andReturn('Claro! Pense na sequência de Fibonacci...');

        $service = new AiOrchestratorService($mockDriver);
        $result = $service->conversarComTutor('Me dê um exemplo', $historico);

        expect($result)->toContain('Fibonacci');
    });
});

describe('AiOrchestratorService — corrigirCodigo (P04)', function () {

    it('envia o prompt P04 com código, enunciado e linguagem', function () {
        $mockDriver = Mockery::mock(LlmServiceInterface::class);
        $mockDriver->shouldReceive('generateContent')
            ->once()
            ->withArgs(function (string $system, string $user, array $options) {
                return str_contains($system, 'Corretor de Código')
                    && str_contains($user, 'function soma')
                    && str_contains($user, 'Implemente uma função')
                    && str_contains($user, 'Linguagem: php')
                    && $options['temperature'] === 0.3;
            })
            ->andReturn('{"aprovado":true,"nota":90,"feedback":{}}');

        $service = new AiOrchestratorService($mockDriver);
        $result = $service->corrigirCodigo(
            'function soma($a, $b) { return $a + $b; }',
            'Implemente uma função que soma dois números.',
            'php',
        );

        expect($result)->toContain('"aprovado":true');
    });

    it('propaga exceção do driver em caso de falha', function () {
        $mockDriver = Mockery::mock(LlmServiceInterface::class);
        $mockDriver->shouldReceive('generateContent')
            ->once()
            ->andThrow(new AiProviderException('Erro na API', 'gemini', 500));

        $service = new AiOrchestratorService($mockDriver);

        $service->corrigirCodigo('code', 'enunciado');
    })->throws(AiProviderException::class, 'Erro na API');
});

// ═══════════════════════════════════════════════════════════════════════════
//  AI ORCHESTRATOR — INJEÇÃO VIA CONSTRUTOR
// ═══════════════════════════════════════════════════════════════════════════

describe('AiOrchestratorService — getDriver', function () {

    it('retorna o driver injetado via construtor', function () {
        $mockDriver = Mockery::mock(LlmServiceInterface::class);
        $mockDriver->shouldReceive('getProviderName')->andReturn('mock');

        $service = new AiOrchestratorService($mockDriver);

        expect($service->getDriver()->getProviderName())->toBe('mock');
    });
});
