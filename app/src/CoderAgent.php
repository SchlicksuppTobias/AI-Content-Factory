<?php

namespace src;

use RuntimeException;

require_once __DIR__ . '/OllamaClient.php';

class CoderAgent
{
    private OllamaClient $ollama;
    private string $model = 'qwen2.5-coder:7b';

    public function __construct(OllamaClient $ollama)
    {
        $this->ollama = $ollama;
    }

    /**
     * @param string $ticket The ticket title / task
     * @param string $projectContext Full project description
     * @param string $existingCode JSON of already written files {path: content}
     *
     * @return array {path: string, content: string}[]
     */
    public function implement(string $ticket, string $projectContext, string $existingCode = '{}'): array
    {
        $system = <<<SYS
You are an expert software engineer. You implement code tickets precisely and completely.

Rules:
- Output ONLY a JSON array of file objects. No markdown, no explanation, no preamble.
- Each file object has exactly two keys: "path" (relative file path) and "content" (full file content as string).
- Write complete, production-ready code. No placeholders, no TODOs.
- Use only standard library / built-in functions unless the project description specifies dependencies.
- Paths are relative to the project root (e.g. "src/Database.php", "index.php").
- If the ticket only modifies an existing file, return only that file's full updated content.

Example:
[{"path":"src/Database.php","content":"<?php\nclass Database { ... }"},{"path":"config/db.php","content":"<?php\nreturn ['host'=>'localhost',...];"}]
SYS;

        $existing = $existingCode !== '{}' ? "\n\nAlready implemented files (for context):\n{$existingCode}" : '';

        $prompt = <<<PROMPT
Project description:
{$projectContext}
{$existing}

Current ticket to implement:
{$ticket}

Output the JSON array of files now.
PROMPT;

        $raw = $this->ollama->generate($this->model, $system, $prompt, 360);

        // Extract JSON array
        if (preg_match('/\[.*\]/s', $raw, $m)) {
            $files = json_decode($m[0], true);
            if (is_array($files)) {
                $valid = [];
                foreach ($files as $f) {
                    if (isset($f['path'], $f['content']) && is_string($f['path']) && is_string($f['content'])) {
                        $valid[] = $f;
                    }
                }
                if (count($valid) > 0) return $valid;
            }
        }

        throw new RuntimeException("Coder returned invalid JSON for ticket '{$ticket}':\n" . substr($raw, 0, 500));
    }
}
