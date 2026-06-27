<?php

namespace src;
/**
 * OllamaClient – wraps the Ollama HTTP API
 */
class OllamaClient
{
    private string $baseUrl;

    public function __construct(string $baseUrl = 'http://localhost:11434')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Send a prompt to a model and return the full response text.
     *
     * @param string $model e.g. 'qwen2.5-coder:7b'
     * @param string $system System prompt
     * @param string $prompt User prompt
     * @param int $timeout Seconds to wait
     */
    public function generate(string $model, string $system, string $prompt, int $timeout = 300): string
    {
        $payload = json_encode([
            'model' => $model,
            'system' => $system,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.2,
                'num_predict' => 4096,
            ],
        ]);

        $ch = curl_init("{$this->baseUrl}/api/generate");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new RuntimeException("Ollama cURL error: $err");
        }

        $data = json_decode($raw, true);
        if (!isset($data['response'])) {
            throw new RuntimeException("Unexpected Ollama response: " . substr($raw, 0, 300));
        }

        return trim($data['response']);
    }

    /** Check if Ollama is reachable */
    public function ping(): bool
    {
        $ch = curl_init("{$this->baseUrl}/api/tags");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        return $raw !== false;
    }
}
