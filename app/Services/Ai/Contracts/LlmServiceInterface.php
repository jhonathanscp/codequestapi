<?php

namespace App\Services\Ai\Contracts;

/**
 * Contrato para comunicação com provedores de LLM (Large Language Models).
 *
 * Cada driver (Gemini, OpenAI, DeepSeek) deve implementar esta interface,
 * abstraindo as diferenças de payload/resposta entre as APIs.
 */
interface LlmServiceInterface
{
    /**
     * Envia um prompt para a LLM e retorna o texto gerado.
     *
     * @param string $systemPrompt Instrução de sistema / persona (role: system).
     * @param string $userMessage  Mensagem do usuário / conteúdo a ser processado.
     * @param array<string, mixed> $options Opções adicionais (temperature, max_tokens, etc.).
     * @return string Texto gerado pela LLM.
     *
     * @throws \App\Services\Ai\Exceptions\AiProviderException Em caso de falha na API.
     */
    public function generateContent(string $systemPrompt, string $userMessage, array $options = []): string;

    /**
     * Retorna o nome do provedor ativo (ex: "gemini", "openai", "deepseek").
     */
    public function getProviderName(): string;
}
