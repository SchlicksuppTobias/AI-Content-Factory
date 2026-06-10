<?php

function callLLM($prompt) {

    $url = "http://ollama:11434/api/generate";

    $data = [
        "model" => "llama3",
        "prompt" => $prompt,
        "stream" => false
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $res = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($res, true);

    return $json["response"] ?? "Fehler beim LLM";
}

function callLLMToFixText($text) {

    $url = "http://ollama:11434/api/generate";

    $prompt = "
Du bist ein professioneller LinkedIn-Redakteur.

AUFGABE:
Du überarbeitest einen KI-generierten LinkedIn Post.

REGELN:
- Schreibe komplett auf DEUTSCH
- Entferne ALLE englischen Wörter (außer Fachbegriffe)
- Entferne Marketing-Floskeln wie:
  - 'Call to Action'
  - 'Attention-Grabber'
  - 'Challenge'
  - 'Insight'
- Kein Meta-Text (keine Überschriften wie 'Title:' oder 'AI Output:')
- Kein Hinweis auf KI oder Prompt
- Mach den Text natürlich, flüssig und menschlich
- LinkedIn Stil: klar, direkt, professionell
- Hashtags am Ende (max 5)

TEXT ZU BEARBEITEN:
$text

Gib nur den finalen LinkedIn Post zurück.
";

    $data = [
        "model" => "mistral",
        "prompt" => $prompt,
        "stream" => false
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $res = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($res, true);

    return $json["response"] ?? "Fehler beim LLM";
}