<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>AI Content Factory</title>

    <style>
        body {
            font-family: Arial;
            background: #0f172a;
            color: white;
            margin: 0;
        }

        header {
            padding: 20px;
            background: #111827;
        }

        .container {
            padding: 40px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .card {
            background: #1f2937;
            padding: 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.2s;
        }

        .card:hover {
            background: #374151;
        }
    </style>
</head>
<body>

<header>
    <h2>🚀 AI Content Factory</h2>
</header>
<div class="container">
    <a href="ideas.php" style="text-decoration:none;color:white;">
        <div class="card">
            <h3>🧠 Ideen Finder</h3>
            <p>Chatte mit der AI für neue Ideen</p>
        </div>
    </a>
    <a href="linkedin.php" style="text-decoration:none;color:white;">
        <div class="card">
            <h3>💼 LinkedIn Generator</h3>
            <p>Erstelle Posts automatisch oder geführt</p>
        </div>
    </a>
    <a href="coder.php" style="text-decoration:none;color:white;">
        <div class="card">
            <h3>💼 Code Generator</h3>
            <p>Erstellt automatisch Code und pusht diesen auf Github</p>
        </div>
    </a>
</div>
</body>
</html>