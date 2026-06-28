<?php

namespace src;

require_once __DIR__ . '/OllamaClient.php';

/**
 * PlannerAgent – uses the Coder model to decompose a project into tickets.
 */
class PlannerAgent
{
    private OllamaClient $ollama;
    private string $model = 'qwen2.5-coder:7b';

    public function __construct(OllamaClient $ollama)
    {
        $this->ollama = $ollama;
    }

    /**
     * Returns an array of ticket title strings.
     */
    public function plan(string $projectDescription): array
    {
        $system = <<<SYS
You are a senior software architect. Your job is to decompose project descriptions into 
small, concrete, independent implementation tickets. Each ticket must be completable in 
one focused coding session and produce a single file or a small set of related files.

Rules:
- Output ONLY a JSON array of ticket title strings. No markdown, no explanation.
- Maximum 10 tickets, minimum 3.
- Each ticket title should be ≤ 80 characters and start with a verb (Create, Add, Implement, Write, …).
- Order tickets by implementation dependency (foundations first).
- Tickets must cover the full project scope.

Example output:
["Create project folder structure and composer.json","Implement database connection class","Create User model with CRUD methods","Write REST router with GET /users and POST /users","Add error handling and JSON response helpers","Write PHPUnit tests for User model"]
SYS;

        $prompt = "Project description:\n\n{$projectDescription}\n\nGenerate the ticket list now.";

        $raw = $this->ollama->generate($this->model, $system, $prompt);

        // Extract JSON array from response (model might add prose)
        if (preg_match('/\[.*\]/s', $raw, $m)) {
            $tickets = json_decode($m[0], true);
            if (is_array($tickets) && count($tickets) > 0) {
                return array_values(array_filter($tickets, 'is_string'));
            }
        }

        throw new RuntimeException("Planner returned invalid JSON:\n" . substr($raw, 0, 500));
    }
}
