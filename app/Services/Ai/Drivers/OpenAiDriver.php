<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Contracts\LlmServiceInterface;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Driver para a API da OpenAI (Chat Completions).
 *
 * Formata o payload no padrão `messages[]` com roles system/user
 * e extrai o texto da resposta `choices[0].message.content`.
 */
class OpenAiDriver implements LlmServiceInterface
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly string $baseUrl,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function generateContent(string $systemPrompt, string $userMessage, array $options = []): string
    {
        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2048,
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$this->apiKey}",
                ])
                ->post($this->baseUrl, $payload);
        } catch (ConnectionException $e) {
            throw new AiProviderException(
                "Timeout ao conectar com a API OpenAI: {$e->getMessage()}",
                $this->getProviderName(),
                408,
                $e,
            );
        }

        if ($response->failed()) {
            throw new AiProviderException(
                "Erro na API OpenAI [{$response->status()}]: {$response->body()}",
                $this->getProviderName(),
                $response->status(),
            );
        }

        $text = $response->json('choices.0.message.content');

        if (is_null($text)) {
            throw new AiProviderException(
                'Resposta da API OpenAI não contém texto gerado.',
                $this->getProviderName(),
                $response->status(),
            );
        }

        return $text;
    }

    /**
     * {@inheritDoc}
     */
    public function getProviderName(): string
    {
        return 'openai';
    }
}
