<?php include "api.php"; ?>

<?php

$response = "";

if ($_POST) {

    $input = $_POST["input"];
    $mode = $_POST["mode"];

    if ($mode === "auto") {

        $prompt = "
Du bist ein erfahrener LinkedIn-Content-Creator.

AUFGABE:
Erstelle einen hochwertigen LinkedIn Post zu einem Thema.

WICHTIGE REGELN:
- Schreibe komplett auf Deutsch
- KEINE englischen Begriffe
- KEINE Meta-Labels wie 'Hook', 'Insight', 'CTA' im Text
- Schreibe natürlich wie ein echter Mensch
- Kein Marketing-Blabla
- Keine übertriebenen Buzzwords
- Keine Einleitung wie 'Hier ist dein Post'
- Kein Hinweis auf KI

STRUKTUR (nur logisch, nicht als Überschrift):
- Starte mit einem starken Hook (1–2 Sätze)
- Beschreibe ein Problem oder eine Beobachtung
- Gib eine klare Erkenntnis oder Einsicht
- Formuliere eine kurze, natürliche Schlussfrage oder Meinung
- 3–5 passende Hashtags am Ende

STIL:
- modern, klar, direkt
- LinkedIn-typisch, aber nicht werblich
- eher wie ein persönlicher Gedanke, kein Werbetext

THEMA:
$input

Gib nur den finalen Post aus.
";

    } else {

        $prompt = "
Du bist ein LinkedIn Coach.

Führe den Nutzer Schritt für Schritt zu einem guten Post.

Stelle Fragen zu:
- Zielgruppe
- Tonalität
- Thema

Input:
$input
";
    }

    $response = callLLM($prompt);
    $response = callLLMToFixText($response);
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>LinkedIn Generator</title>

    <style>
        body {
            font-family: Arial;
            background: #0f172a;
            color: white;
        }

        .container {
            width: 60%;
            margin: auto;
            margin-top: 50px;
        }

        textarea {
            width: 100%;
            height: 100px;
        }

        button {
            padding: 10px;
            margin-top: 10px;
            background: #2563eb;
            color: white;
            border: none;
            margin-right: 10px;
        }

        .box {
            margin-top: 20px;
            background: #1f2937;
            padding: 15px;
            border-radius: 10px;
        }
    </style>

</head>
<body>

<div class="container">

    <h2>💼 LinkedIn Generator</h2>

    <form method="POST">

        <textarea name="input" placeholder="Thema..."></textarea><br>

        <button name="mode" value="auto">🤖 Auto Post</button>
        <button name="mode" value="guided">✍️ Guided</button>

    </form>

    <?php if ($response): ?>
        <div class="box">
            <b>AI Output:</b><br><br>
            <?php echo nl2br(htmlspecialchars($response)); ?>
        </div>
    <?php endif; ?>

    <br>
    <a href="index.php" style="color:#60a5fa;">← zurück</a>

</div>

</body>
</html>