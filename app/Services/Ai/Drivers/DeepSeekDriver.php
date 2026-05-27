<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Contracts\LlmServiceInterface;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Driver para a API DeepSeek (compatível com o formato OpenAI Chat Completions).
 *
 * A API do DeepSeek segue a mesma especificação de mensagens da OpenAI,
 * diferindo apenas na URL base e modelo padrão.
 */
class DeepSeekDriver implements LlmServiceInterface
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
                "Timeout ao conectar com a API DeepSeek: {$e->getMessage()}",
                $this->getProviderName(),
                408,
                $e,
            );
        }

        if ($response->failed()) {
            throw new AiProviderException(
                "Erro na API DeepSeek [{$response->status()}]: {$response->body()}",
                $this->getProviderName(),
                $response->status(),
            );
        }

        $text = $response->json('choices.0.message.content');

        if (is_null($text)) {
            throw new AiProviderException(
                'Resposta da API DeepSeek não contém texto gerado.',
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
        return 'deepseek';
    }
}
