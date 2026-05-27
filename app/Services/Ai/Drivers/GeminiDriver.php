<?php

namespace App\Services\Ai\Drivers;

use App\Services\Ai\Contracts\LlmServiceInterface;
use App\Services\Ai\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Driver para a API Google Gemini (Generative Language).
 *
 * Formata o payload no padrão `contents[]` esperado pela API REST do Gemini
 * e extrai o texto da resposta `candidates[0].content.parts[0].text`.
 */
class GeminiDriver implements LlmServiceInterface
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
        $url = sprintf('%s/%s:generateContent?key=%s', $this->baseUrl, $this->model, $this->apiKey);

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $userMessage]],
                ],
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'maxOutputTokens' => $options['max_tokens'] ?? 2048,
            ],
        ];

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            throw new AiProviderException(
                "Timeout ao conectar com a API Gemini: {$e->getMessage()}",
                $this->getProviderName(),
                408,
                $e,
            );
        }

        if ($response->failed()) {
            throw new AiProviderException(
                "Erro na API Gemini [{$response->status()}]: {$response->body()}",
                $this->getProviderName(),
                $response->status(),
            );
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (is_null($text)) {
            throw new AiProviderException(
                'Resposta da API Gemini não contém texto gerado.',
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
        return 'gemini';
    }
}
