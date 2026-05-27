<?php

namespace App\Services\Ai\Exceptions;

use RuntimeException;

/**
 * Exception lançada quando uma chamada ao provedor de IA falha.
 *
 * Encapsula erros de HTTP (timeout, 4xx, 5xx) e respostas inesperadas,
 * provendo contexto sobre o provedor e status code envolvidos.
 */
class AiProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly string $provider,
        private readonly int $statusCode = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Nome do provedor que originou o erro (ex: "gemini", "openai").
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * HTTP Status Code retornado pela API (0 se não aplicável).
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
