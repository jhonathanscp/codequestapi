<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\LlmServiceInterface;
use App\Services\Ai\Drivers\DeepSeekDriver;
use App\Services\Ai\Drivers\GeminiDriver;
use App\Services\Ai\Drivers\OpenAiDriver;
use InvalidArgumentException;

/**
 * Serviço orquestrador de IA da plataforma CODE QUEST.
 *
 * Responsabilidades:
 * - Resolver o driver de LLM correto com base na configuração (AI_PROVIDER).
 * - Gerenciar a injeção de contexto (memory) nos prompts.
 * - Executar os 4 prompts definidos no AGENTS.md (P01–P04).
 *
 * O serviço é registrado como singleton no container do Laravel,
 * e o driver concreto é instanciado via factory method.
 */
class AiOrchestratorService
{
    private LlmServiceInterface $driver;

    public function __construct(?LlmServiceInterface $driver = null)
    {
        $this->driver = $driver ?? self::resolveDriver();
    }

    /**
     * Factory method que instancia o driver correto com base no config.
     *
     * @throws InvalidArgumentException Se o provedor configurado não for suportado.
     */
    public static function resolveDriver(): LlmServiceInterface
    {
        $provider = config('services.ai.provider') ?? 'gemini';

        return match ($provider) {
            'gemini' => new GeminiDriver(
                apiKey: (string) config('services.gemini.key') ?? '',
                model: (string) config('services.gemini.model') ?? 'gemini-2.0-flash',
                baseUrl: (string) config('services.gemini.url') ?? '',
            ),
            'openai' => new OpenAiDriver(
                apiKey: (string) config('services.openai.key') ?? '',
                model: (string) config('services.openai.model') ?? 'gpt-4o-mini',
                baseUrl: (string) config('services.openai.url') ?? '',
            ),
            'deepseek' => new DeepSeekDriver(
                apiKey: (string) config('services.deepseek.key') ?? '',
                model: (string) config('services.deepseek.model') ?? 'deepseek-chat',
                baseUrl: (string) config('services.deepseek.url') ?? '',
            ),
            default => throw new InvalidArgumentException(
                "Provedor de IA [{$provider}] não é suportado. Use: gemini, openai ou deepseek."
            ),
        };
    }

    /**
     * Retorna o driver de LLM ativo.
     */
    public function getDriver(): LlmServiceInterface
    {
        return $this->driver;
    }

    // ─── P01 — Gerador de Nivelamento ────────────────────────────────────

    /**
     * Gera questões de nivelamento para um tema específico.
     *
     * @param string $tema Tema das questões (ex: "Estruturas de Dados").
     * @return string JSON com as questões geradas pela LLM.
     */
    public function gerarNivelamento(string $tema): string
    {
        $systemPrompt = PromptRegistry::get('P01');
        $userMessage = "Gere 3 questões de nivelamento sobre o tema: {$tema}";

        return $this->driver->generateContent($systemPrompt, $userMessage, [
            'temperature' => 0.5,
            'max_tokens' => 2048,
        ]);
    }

    // ─── P02 — Construtor de Trilha ──────────────────────────────────────

    /**
     * Gera uma trilha de aprendizagem personalizada.
     *
     * @param string $nivel Nível do aluno (ex: "iniciante", "intermediario", "avancado").
     * @param array<int, string> $interesses Stack de interesse do aluno (ex: ["PHP", "Laravel"]).
     * @param array<string, mixed> $contextoNivelamento Resultados do teste de nivelamento (memory).
     * @return string JSON com a trilha gerada pela LLM.
     */
    public function construirTrilha(string $nivel, array $interesses, array $contextoNivelamento = []): string
    {
        $systemPrompt = PromptRegistry::get('P02');

        $userMessage = "Nível do aluno: {$nivel}\n"
            . "Interesses: " . implode(', ', $interesses) . "\n";

        if (!empty($contextoNivelamento)) {
            $userMessage .= "Resultados do nivelamento: " . json_encode($contextoNivelamento, JSON_UNESCAPED_UNICODE);
        }

        return $this->driver->generateContent($systemPrompt, $userMessage, [
            'temperature' => 0.6,
            'max_tokens' => 4096,
        ]);
    }

    // ─── P03 — Tutor Virtual ─────────────────────────────────────────────

    /**
     * Envia uma mensagem ao Tutor Virtual e retorna a resposta.
     *
     * @param string $mensagemAluno Mensagem/dúvida do aluno.
     * @param array<int, array{role: string, content: string}> $historico Histórico de conversa (memory).
     * @return string Resposta do tutor em Markdown.
     */
    public function conversarComTutor(string $mensagemAluno, array $historico = []): string
    {
        $systemPrompt = PromptRegistry::get('P03');

        $userMessage = '';
        if (!empty($historico)) {
            $userMessage .= "Histórico da conversa:\n";
            foreach ($historico as $msg) {
                $role = $msg['role'] === 'user' ? 'Aluno' : 'Tutor';
                $userMessage .= "[{$role}]: {$msg['content']}\n";
            }
            $userMessage .= "\n";
        }

        $userMessage .= "Mensagem atual do aluno: {$mensagemAluno}";

        return $this->driver->generateContent($systemPrompt, $userMessage, [
            'temperature' => 0.7,
            'max_tokens' => 1024,
        ]);
    }

    // ─── P04 — Corretor de Código ────────────────────────────────────────

    /**
     * Submete o código do aluno para correção automatizada.
     *
     * @param string $codigo Código-fonte submetido pelo aluno.
     * @param string $enunciado Descrição do desafio/problema.
     * @param string $linguagem Linguagem de programação (ex: "php", "python").
     * @return string JSON com o resultado da correção.
     */
    public function corrigirCodigo(string $codigo, string $enunciado, string $linguagem = 'php'): string
    {
        $systemPrompt = PromptRegistry::get('P04');

        $userMessage = "Linguagem: {$linguagem}\n\n"
            . "Enunciado do desafio:\n{$enunciado}\n\n"
            . "Código submetido:\n```{$linguagem}\n{$codigo}\n```";

        return $this->driver->generateContent($systemPrompt, $userMessage, [
            'temperature' => 0.3,
            'max_tokens' => 2048,
        ]);
    }
}
