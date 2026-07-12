<?php

echo "<h1>AI Content Factory läuft 🚀</h1>";

// Test: MariaDB
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli("mariadb", "app", "app", "app");
    echo "<p>✅ MariaDB Verbindung OK</p>";
} catch (Exception $e) {
    echo "<p>❌ DB Fehler: " . $e->getMessage() . "</p>";
}


// Test: Redis
try {
    $redis = new Redis();
    $redis->connect('redis', 6379);
    $redis->set("test", "hello");
    echo "<p>✅ Redis OK: " . $redis->get("test") . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Redis Fehler: " . $e->getMessage() . "</p>";
}

// Test: Ollama
$response = @file_get_contents("http://ollama:11434/api/tags");

if ($response) {

    $data = json_decode($response, true);

    echo "<p>✅ Ollama erreichbar</p>";

    if (!empty($data["models"])) {

        echo "<h3>📦 Verfügbare Modelle:</h3>";
        echo "<ul>";

        foreach ($data["models"] as $model) {
            echo "<li>";
            echo htmlspecialchars($model["name"]);

            if (!empty($model["size"])) {
                echo " (" . round($model["size"] / 1024 / 1024 / 1024, 2) . " GB)";
            }

            echo "</li>";
        }

        echo "</ul>";

    } else {
        echo "<p>⚠️ Keine Modelle installiert</p>";
    }
} else {
    echo "<p>❌ Ollama nicht erreichbar</p>";
}