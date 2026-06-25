# AI Dev Agent

Ein lokaler KI-Coding-Agent der Projekte plant, implementiert, reviewed, testet und auf Git pusht.

## Stack

| Agent    | Modell            | Aufgabe                          |
|----------|-------------------|----------------------------------|
| Planner  | qwen2.5-coder:7b  | Projekt → Tickets                |
| Coder    | qwen2.5-coder:7b  | Ticket → Code-Dateien            |
| Reviewer | qwen2.5-coder:7b  | Code-Review & Fixes              |
| Tester   | llama3.1:8b       | PHPUnit-Tests generieren         |

> Optimiert für 8–12 GB VRAM.

---

## Voraussetzungen

- **PHP 8.1+** mit `curl`-Extension
- **Ollama** lokal installiert: https://ollama.ai
- **Git** konfiguriert (SSH-Key oder Credential-Helper für HTTPS)

## Modelle herunterladen

```bash
ollama pull qwen2.5-coder:7b
ollama pull llama3.1:8b
```

## Ordnerstruktur

```
ai-dev-agent/
├── public/
│   └── index.php        ← Einstiegspunkt / UI
├── api/
│   └── pipeline.php     ← SSE-Endpoint (Pipeline-Orchestrierung)
├── src/
│   ├── OllamaClient.php
│   ├── PlannerAgent.php
│   ├── CoderAgent.php
│   ├── ReviewerAgent.php
│   ├── TesterAgent.php
│   └── GitManager.php
├── src/
│   └── SSE.php
└── .htaccess
```

## Setup mit PHP Built-in Server (Entwicklung)

```bash
# Im Projektroot:
php -S localhost:8080 -t public/
```

Öffne dann `http://localhost:8080` im Browser.

**Achtung:** Der `api/`-Ordner muss ebenfalls erreichbar sein. Einfachster Weg:

```bash
# Vom Projektroot aus:
php -S localhost:8080
```

und rufe `http://localhost:8080/public/index.php` auf.

## Setup mit Apache / Nginx

Dokumenten-Root auf das Projektverzeichnis setzen. Die `.htaccess` übernimmt das Routing.

## Git-Credentials

Für HTTPS-Repos: Nutze einen Personal Access Token im URL:
```
https://TOKEN@github.com/user/repo.git
```

Für SSH: Stelle sicher, dass dein SSH-Key in `~/.ssh/` liegt und `ssh-agent` läuft.

## Pipeline-Ablauf

1. **Projektbeschreibung** eingeben
2. **Repository-URL** angeben (muss existieren & pushbar sein)
3. `▶ Pipeline starten` klicken
4. Planner zerlegt Projekt in 3–10 Tickets
5. Für jedes Ticket: Coder → Reviewer → Tester
6. Alle Dateien werden committed & gepusht

## Hinweise

- Die Pipeline braucht je nach Projekt 5–20 Minuten (lokale LLMs sind langsam).
- Der Temp-Klon wird nach dem Push automatisch gelöscht.
- Fehler in einzelnen Tickets unterbrechen die Pipeline nicht.
