<?php

use App\Services\Ai\Drivers\DeepSeekDriver;
use App\Services\Ai\Drivers\GeminiDriver;
use App\Services\Ai\Drivers\OpenAiDriver;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Testes Unitários — LLM Drivers (Gemini, OpenAI, DeepSeek)
|--------------------------------------------------------------------------
|
| Todos os testes utilizam Http::fake() para simular as respostas das APIs.
| Nenhuma chamada real é feita. Os testes verificam:
| - Formatação correta do payload enviado
| - Extração correta do texto da resposta
| - Tratamento de erros HTTP (500, 429, timeout)
| - Tratamento de respostas malformadas
|
*/

// ═══════════════════════════════════════════════════════════════════════════
//  GEMINI DRIVER
// ═══════════════════════════════════════════════════════════════════════════

describe('GeminiDriver', function () {

    beforeEach(function () {
        $this->driver = new GeminiDriver(
            apiKey: 'fake-gemini-key',
            model: 'gemini-2.0-flash',
            baseUrl: 'https://generativelanguage.googleapis.com/v1beta/models',
        );
    });

    it('envia payload no formato correto e retorna texto gerado', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Resposta da IA Gemini'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $result = $this->driver->generateContent(
            'Você é um assistente.',
            'Explique recursão.',
        );

        expect($result)->toBe('Resposta da IA Gemini');

        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), 'gemini-2.0-flash:generateContent')
                && str_contains($request->url(), 'key=fake-gemini-key')
                && $body['system_instruction']['parts'][0]['text'] === 'Você é um assistente.'
                && $body['contents'][0]['role'] === 'user'
                && $body['contents'][0]['parts'][0]['text'] === 'Explique recursão.';
        });
    });

    it('aceita options de temperature e max_tokens', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'OK']]]],
                ],
            ], 200),
        ]);

        $this->driver->generateContent('sys', 'msg', [
            'temperature' => 0.2,
            'max_tokens' => 500,
        ]);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['generationConfig']['temperature'] === 0.2
                && $body['generationConfig']['maxOutputTokens'] === 500;
        });
    });

    it('lança AiProviderException em erro HTTP 500', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                ['error' => ['message' => 'Internal Server Error']],
                500,
            ),
        ]);

        $this->driver->generateContent('sys', 'msg');
    })->throws(AiProviderException::class, 'Erro na API Gemini [500]');

    it('lança AiProviderException em erro HTTP 429 (rate limit)', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
                ['error' => ['message' => 'Rate limit exceeded']],
                429,
            ),
        ]);

        try {
            $this->driver->generateContent('sys', 'msg');
        } catch (AiProviderException $e) {
            expect($e->getProvider())->toBe('gemini')
                ->and($e->getStatusCode())->toBe(429);

            return;
        }

        $this->fail('Expected AiProviderException was not thrown.');
    });

    it('lança AiProviderException quando a resposta não contém texto', function () {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => []]]],
            ], 200),
        ]);

        $this->driver->generateContent('sys', 'msg');
    })->throws(AiProviderException::class, 'não contém texto gerado');

    it('lança AiProviderException em timeout de conexão', function () {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        try {
            $this->driver->generateContent('sys', 'msg');
        } catch (AiProviderException $e) {
            expect($e->getProvider())->toBe('gemini')
                ->and($e->getStatusCode())->toBe(408)
                ->and($e->getMessage())->toContain('Timeout');

            return;
        }

        $this->fail('Expected AiProviderException was not thrown.');
    });

    it('retorna o nome correto do provedor', function () {
        expect($this->driver->getProviderName())->toBe('gemini');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
//  OPENAI DRIVER
// ═══════════════════════════════════════════════════════════════════════════

describe('OpenAiDriver', function () {

    beforeEach(function () {
        $this->driver = new OpenAiDriver(
            apiKey: 'fake-openai-key',
            model: 'gpt-4o-mini',
            baseUrl: 'https://api.openai.com/v1/chat/completions',
        );
    });

    it('envia payload no formato messages[] e retorna texto gerado', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Resposta da IA OpenAI']],
                ],
            ], 200),
        ]);

        $result = $this->driver->generateContent(
            'Você é um tutor.',
            'O que é um array?',
        );

        expect($result)->toBe('Resposta da IA OpenAI');

        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), 'api.openai.com')
                && $body['model'] === 'gpt-4o-mini'
                && $body['messages'][0] === ['role' => 'system', 'content' => 'Você é um tutor.']
                && $body['messages'][1] === ['role' => 'user', 'content' => 'O que é um array?']
                && $request->hasHeader('Authorization', 'Bearer fake-openai-key');
        });
    });

    it('lança AiProviderException em erro HTTP 500', function () {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'fail'], 500),
        ]);

        $this->driver->generateContent('sys', 'msg');
    })->throws(AiProviderException::class, 'Erro na API OpenAI [500]');

    it('lança AiProviderException quando a resposta não contém texto', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => null]]],
            ], 200),
        ]);

        $this->driver->generateContent('sys', 'msg');
    })->throws(AiProviderException::class, 'não contém texto gerado');

    it('lança AiProviderException em timeout de conexão', function () {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        try {
            $this->driver->generateContent('sys', 'msg');
        } catch (AiProviderException $e) {
            expect($e->getProvider())->toBe('openai')
                ->and($e->getStatusCode())->toBe(408);

            return;
        }

        $this->fail('Expected AiProviderException was not thrown.');
    });

    it('retorna o nome correto do provedor', function () {
        expect($this->driver->getProviderName())->toBe('openai');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
//  DEEPSEEK DRIVER
// ═══════════════════════════════════════════════════════════════════════════

describe('DeepSeekDriver', function () {

    beforeEach(function () {
        $this->driver = new DeepSeekDriver(
            apiKey: 'fake-deepseek-key',
            model: 'deepseek-chat',
            baseUrl: 'https://api.deepseek.com/v1/chat/completions',
        );
    });

    it('envia payload no formato messages[] e retorna texto gerado', function () {
        Http::fake([
            'api.deepseek.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Resposta da IA DeepSeek']],
                ],
            ], 200),
        ]);

        $result = $this->driver->generateContent(
            'Você é um corretor.',
            'Analise este código.',
        );

        expect($result)->toBe('Resposta da IA DeepSeek');

        Http::assertSent(function ($request) {
            $body = $request->data();

            return str_contains($request->url(), 'api.deepseek.com')
                && $body['model'] === 'deepseek-chat'
                && $request->hasHeader('Authorization', 'Bearer fake-deepseek-key');
        });
    });

    it('lança AiProviderException em erro HTTP 500', function () {
        Http::fake([
            'api.deepseek.com/*' => Http::response(['error' => 'fail'], 500),
        ]);

        $this->driver->generateContent('sys', 'msg');
    })->throws(AiProviderException::class, 'Erro na API DeepSeek [500]');

    it('lança AiProviderException em timeout de conexão', function () {
        Http::fake(function () {
            throw new ConnectionException('Connection timed out');
        });

        try {
            $this->driver->generateContent('sys', 'msg');
        } catch (AiProviderException $e) {
            expect($e->getProvider())->toBe('deepseek')
                ->and($e->getStatusCode())->toBe(408);

            return;
        }

        $this->fail('Expected AiProviderException was not thrown.');
    });

    it('retorna o nome correto do provedor', function () {
        expect($this->driver->getProviderName())->toBe('deepseek');
    });
});
