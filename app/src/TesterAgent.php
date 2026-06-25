<?php

namespace src;

require_once __DIR__ . '/OllamaClient.php';

/**
 * TesterAgent – generates test files for implemented code.
 */
class TesterAgent
{
    private OllamaClient $ollama;
    private string $model = 'llama3.1:8b';

    public function __construct(OllamaClient $ollama)
    {
        $this->ollama = $ollama;
    }

    /**
     * @param string $ticket Original ticket
     * @param array $files [{path, content}]
     *
     * @return array  Test files [{path, content}] (may be empty if not applicable)
     */
    public function generateTests(string $ticket, array $files): array
    {
        $filesJson = json_encode($files, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $system = <<<SYS
You are a QA engineer who writes tests for PHP code.

Rules:
- Output ONLY a JSON array of file objects with "path" and "content". No prose.
- Write PHPUnit tests if the code has testable classes/functions.
- Place tests in a "tests/" directory, mirroring the source structure.
- If no testable logic exists (e.g. config-only files), return an empty array [].
- Keep tests focused: one test class per source class.
- Use realistic test data.
- Do NOT require external services; mock or stub as needed.
SYS;

        $prompt = <<<PROMPT
Ticket: {$ticket}

Implemented files:
{$filesJson}

Generate test files now.
PROMPT;

        $raw = $this->ollama->generate($this->model, $system, $prompt, 300);

        if (preg_match('/\[.*\]/s', $raw, $m)) {
            $tests = json_decode($m[0], true);
            if (is_array($tests)) {
                $valid = [];
                foreach ($tests as $f) {
                    if (isset($f['path'], $f['content'])) $valid[] = $f;
                }
                return $valid;
            }
        }

        return []; // Tests optional – don't fail pipeline
    }
}
