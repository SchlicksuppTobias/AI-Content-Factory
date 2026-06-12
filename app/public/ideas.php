<?php include "api.php"; ?>

<?php

$response = "";

if ($_POST) {
    $input = $_POST["input"];

    $prompt = "Du bist ein kreativer Ideen-Assistent. Gib konkrete, umsetzbare Ideen.\n\nUser: $input";

    $response = callLLM($prompt);
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Ideen Finder</title>

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

    <h2>🧠 Ideen Finder</h2>

    <form method="POST">
        <textarea name="input" placeholder="Deine Idee..."></textarea><br>
        <button>Senden</button>
    </form>

    <?php if ($response): ?>
        <div class="box">
            <b>AI Antwort:</b><br><br>
            <?php echo nl2br(htmlspecialchars($response)); ?>
        </div>
    <?php endif; ?>

    <br>
    <a href="index.php" style="color:#60a5fa;">← zurück</a>

</div>

</body>
</html>