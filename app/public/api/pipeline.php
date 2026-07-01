<?php
set_time_limit(0);
ignore_user_abort(true);
/**
 * pipeline.php – SSE endpoint that orchestrates the full AI Dev Agent pipeline:
 * 1. Plan tickets  (Planner/Coder model)
 * 2. For each ticket: Code → Review → Test
 * 3. Write all files to temp repo clone
 * 4. Commit & push to Git
 */

use src\CoderAgent;
use src\GitManager;
use src\OllamaClient;
use src\PlannerAgent;
use src\ReviewerAgent;
use src\SSE;
use src\TesterAgent;

require_once __DIR__ . '/../../src/SSE.php';
require_once __DIR__ . '/../../src/OllamaClient.php';
require_once __DIR__ . '/../../src/PlannerAgent.php';
require_once __DIR__ . '/../../src/CoderAgent.php';
require_once __DIR__ . '/../../src/ReviewerAgent.php';
require_once __DIR__ . '/../../src/TesterAgent.php';
require_once __DIR__ . '/../../src/GitManager.php';

SSE::init();

// ── Input validation ─────────────────────────────────────────────────────────

$desc   = trim($_GET['desc']   ?? '');
$repo   = trim($_GET['repo']   ?? '');
$branch = trim($_GET['branch'] ?? 'main');
$ollama = trim($_GET['ollama'] ?? 'http://localhost:11434');

if (!$desc || !$repo) {
    SSE::error('Beschreibung und Repository-URL sind erforderlich.');
    exit;
}

if (!filter_var($repo, FILTER_VALIDATE_URL) && !preg_match('/^git@/', $repo)) {
    SSE::error('Ungültige Repository-URL.');
    exit;
}

// ── Bootstrap ────────────────────────────────────────────────────────────────

$client   = new OllamaClient($ollama);
$planner  = new PlannerAgent($client);
$coder    = new CoderAgent($client);
$reviewer = new ReviewerAgent($client);
$tester   = new TesterAgent($client);

// ── Step 1: Ping Ollama ──────────────────────────────────────────────────────

SSE::log('🔌 Verbinde mit Ollama (' . $ollama . ')…', 'dim');
if (!$client->ping()) {
    SSE::error("Ollama nicht erreichbar unter: {$ollama}\nStelle sicher, dass Ollama läuft.");
    exit;
}
SSE::log('✅ Ollama verbunden.', 'success');

// ── Step 2: Clone repo ───────────────────────────────────────────────────────

SSE::log("\n📦 Klone Repository…", 'header');
SSE::log("   {$repo} [{$branch}]", 'dim');

$git = new GitManager($repo, $branch);
try {
    $git->clone();
    SSE::log('✅ Repository geklont.', 'success');
} catch (Throwable $e) {
    SSE::error('Git clone fehlgeschlagen: ' . $e->getMessage());
    exit;
}

// ── Step 3: Plan tickets ─────────────────────────────────────────────────────

SSE::log("\n🧠 Plane Tickets (qwen2.5-coder:7b)…", 'header');

try {
    $tickets = $planner->plan($desc);
} catch (Throwable $e) {
    SSE::error('Planner fehlgeschlagen: ' . $e->getMessage());
    $git->cleanup();
    exit;
}

// Send ticket list to frontend
SSE::send(['type' => 'tickets', 'tickets' => $tickets]);

$allFiles = []; // path => content  (accumulated across tickets)

// ── Step 4: Process each ticket ──────────────────────────────────────────────

foreach ($tickets as $idx => $ticket) {
    $num = $idx + 1;
    $total = count($tickets);

    SSE::send(['type' => 'ticket_start', 'idx' => $idx]);
    SSE::log("\n── Ticket {$num}/{$total}: {$ticket}", 'header');

    // Build context of already-written files (trimmed to save VRAM)
    $contextFiles = [];
    foreach ($allFiles as $path => $content) {
        $contextFiles[$path] = mb_substr($content, 0, 800) . (mb_strlen($content) > 800 ? "\n// … (truncated)" : '');
    }
    $existingJson = json_encode($contextFiles);

    // ── Coder ────────────────────────────────────────────────────────────────
    SSE::log("   🖊  Coder implementiert…", 'dim');
    try {
        $files = $coder->implement($ticket, $desc, $existingJson);
        SSE::log("   → " . count($files) . " Datei(en) generiert: " . implode(', ', array_column($files, 'path')), 'code');
    } catch (Throwable $e) {
        SSE::log("   ❌ Coder fehlgeschlagen: " . $e->getMessage(), 'error');
        SSE::send(['type' => 'ticket_error', 'idx' => $idx]);
        continue;
    }

    // ── Reviewer ─────────────────────────────────────────────────────────────
    SSE::log("   🔍 Reviewer prüft Code…", 'dim');
    try {
        $files = $reviewer->review($ticket, $files);
        SSE::log("   ✅ Review abgeschlossen.", 'success');
    } catch (Throwable $e) {
        SSE::log("   ⚠ Reviewer-Fehler (fahre fort): " . $e->getMessage(), 'warn');
    }

    // ── Tester ───────────────────────────────────────────────────────────────
    SSE::log("   🧪 Tester generiert Tests…", 'dim');
    try {
        $tests = $tester->generateTests($ticket, $files);
        if (count($tests)) {
            SSE::log("   → " . count($tests) . " Test-Datei(en): " . implode(', ', array_column($tests, 'path')), 'code');
            $files = array_merge($files, $tests);
        } else {
            SSE::log("   ℹ Keine Tests generiert.", 'dim');
        }
    } catch (Throwable $e) {
        SSE::log("   ⚠ Tester-Fehler (fahre fort): " . $e->getMessage(), 'warn');
    }

    // ── Write files ──────────────────────────────────────────────────────────
    foreach ($files as $file) {
        try {
            $git->writeFile($file['path'], $file['content']);
            $allFiles[$file['path']] = $file['content'];
        } catch (Throwable $e) {
            SSE::log("   ⚠ Datei-Fehler ({$file['path']}): " . $e->getMessage(), 'warn');
        }
    }

    SSE::send(['type' => 'ticket_done', 'idx' => $idx]);
}

// ── Step 5: Commit & Push ────────────────────────────────────────────────────

SSE::log("\n🚀 Committe und pushe…", 'header');

$commitMsg = "feat: AI-generated implementation\n\n" . wordwrap($desc, 72, "\n");
$commitMsg .= "\n\nTickets:\n" . implode("\n", array_map(fn($t, $i) => "- TICKET-" . str_pad($i+1, 3, '0', STR_PAD_LEFT) . ": $t", $tickets, array_keys($tickets)));

try {
    $git->commitAndPush($commitMsg);
    SSE::log('✅ Push erfolgreich!', 'success');
    SSE::log("   Branch: {$branch}", 'dim');
    SSE::log("   Dateien: " . count($allFiles), 'dim');
} catch (Throwable $e) {
    SSE::log("❌ Push fehlgeschlagen: " . $e->getMessage(), 'error');
    SSE::log("   Tipp: Prüfe Git-Credentials / SSH-Key.", 'warn');
} finally {
    $git->cleanup();
}

// ── Done ─────────────────────────────────────────────────────────────────────

SSE::log("\n✨ Pipeline abgeschlossen.", 'success');
SSE::done();
