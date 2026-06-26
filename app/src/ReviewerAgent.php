<?php

namespace src;

require_once __DIR__ . '/OllamaClient.php';

/**
 * ReviewerAgent – reviews implemented files and suggests / applies fixes.
 */
class ReviewerAgent
{
    private OllamaClient $ollama;
    private string $model = 'qwen2.5-coder:7b';

    public function __construct(OllamaClient $ollama)
    {
        $this->ollama = $ollama;
    }

    /**
     * @param string $ticket The original ticket description
     * @param array $files [{path, content}]
     *
     * @return array  Reviewed (possibly corrected) [{path, content}]
     */
    public function review(string $ticket, array $files): array
    {
        $filesJson = json_encode($files, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $system = <<<SYS
You are a senior code reviewer. Review the provided implementation for a ticket.

Check for:
1. Correctness – does the code actually fulfil the ticket requirement?
2. Security – no obvious injection vulnerabilities, path traversal, etc.
3. Error handling – exceptions and edge cases handled.
4. Code quality – clean, readable, no dead code.

Rules:
- Output ONLY a JSON array of file objects (same format as input). No prose.
- If code is correct, return it UNCHANGED.
- If you find issues, return the FIXED version of the affected files.
- Never add files that weren't in the input.
SYS;

        $prompt = <<<PROMPT
Ticket: {$ticket}

Files to review:
{$filesJson}

Return the reviewed JSON array now.
PROMPT;

        $raw = $this->ollama->generate($this->model, $system, $prompt, 360);

        if (preg_match('/\[.*\]/s', $raw, $m)) {
            $files = json_decode($m[0], true);
            if (is_array($files)) {
                $valid = [];
                foreach ($files as $f) {
                    if (isset($f['path'], $f['content'])) $valid[] = $f;
                }
                if (count($valid) > 0) return $valid;
            }
        }

        // If reviewer fails to return valid JSON, return originals untouched
        return $files ?? [];
    }
}
