---
trigger: always_on
---

# CODE QUEST API

## 1. Visão Geral do Projeto
A **CODE QUEST** é uma plataforma de estudos gamificada para estudantes de Ciência da Computação. O sistema utiliza IA Generativa para criar trilhas de aprendizagem personalizadas (estilo roadmap do Duolingo) após um teste de nivelamento técnico. Esse é o repositório da API que apenas receberá requests e retornará reponses em JSON para o frontend.

- **Público-alvo:** Estudantes de tecnologia.
- **Diferencial:** Personalização profunda via IA, tutor inteligente integrado e validação de código em tempo real.

## 2. Stack Tecnológica
- **Backend:** PHP 8.5+ / Laravel 13.
- **Frontend:** React (Vite) + Tailwind CSS (SPA Desacoplada).
- **Banco de Dados:** PostgreSQL.
- **IA/Orquestração:** LangChain (PHP/JS) integrando OpenAI, Gemini e DeepSeek.
- **Estética:** Dark Mode, Cyberpunk/Tech ("CORE_SYSTEM").

## 3. Arquitetura de Prompts (Core AI)
O desenvolvimento deve respeitar rigorosamente os prompts definidos na documentação técnica:

| ID | Nome | Técnica | Objetivo |
|:---|:---|:---|:---|
| **P01** | Gerador de Nivelamento | Zero-shot | Criar 3 questões de múltipla escolha para onboarding. |
| **P02** | Construtor de Trilha | Few-shot | Gerar JSON de módulos/nós (ex: Arrays -> Listas -> Árvores). |
| **P03** | Tutor Virtual | Role + CoT | Mentoria técnica passo a passo sem entregar o código pronto. |
| **P04** | Corretor de Código | Chain of Verification | Validar lógica e segurança da submissão do aluno. |

## 4. Endpoints da API (REST)
O Agente deve implementar/manter os seguintes endpoints no Laravel:

- `POST /api/auth/register` & `login` (Sanctum).
- `GET /api/assessment/questions` (Gera perguntas via P01).
- `POST /api/assessment/submit` (Processa respostas e gera trilha via P02).
- `GET /api/roadmap` (Retorna o estado atual da trilha).
- `POST /api/roadmap/nodes/{id}/submit` (Valida desafio via P04).
- `POST /api/tutor/message` (Chat com Tutor via P03).
- `GET /api/ranking/global` (Leaderboard de XP).

## 5. Regras de Estilo e Padrões de Código
- **Backend:** Seguir PSR-12. Usar Controllers enxutos e Services para a lógica de IA. Implementar também Interfaces e Requests
- **IA:** Sempre garantir que as respostas da IA para o usuário sejam didáticas. Nunca gerar código pronto para o aluno na aba do Tutor (P03).
- **Testes Unitários:** O desenvolvimento deve ser estritamente orientado a testes unitários. No Laravel, utilizar Pest. Garantir cobertura de testes para a lógica de negócio e integrações de IA.
- **Tom de Voz:** Profissional, técnico e encorajador. Evitar gírias excessivas ou estilo "jovem demais".

## 6. Fluxo de Trabalho
1. Consultar sempre este arquivo antes de criar novos componentes ou funções de IA.
2. Priorizar a segurança no tratamento de prompts (evitar Prompt Injection).
3. Garantir que a saída do `P02` seja um JSON válido para que o React possa renderizar a trilha dinamicamente.