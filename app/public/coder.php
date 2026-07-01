<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI Dev Agent</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg:        #0d0f14;
      --surface:   #141720;
      --border:    #1f2433;
      --muted:     #2a3045;
      --text:      #e2e8f0;
      --text-dim:  #64748b;
      --accent:    #6ee7b7;   /* terminal green */
      --accent2:   #818cf8;   /* indigo */
      --warn:      #fbbf24;
      --err:       #f87171;
      --radius:    10px;
      --mono:      'JetBrains Mono', monospace;
      --sans:      'Inter', sans-serif;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: var(--sans);
      min-height: 100vh;
      display: grid;
      grid-template-rows: auto 1fr;
    }

    /* ── Header ── */
    header {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 18px 32px;
      border-bottom: 1px solid var(--border);
      background: var(--surface);
    }
    .logo {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-family: var(--mono);
      font-size: 16px; font-weight: 600; color: var(--bg);
    }
    header h1 { font-size: 18px; font-weight: 600; letter-spacing: -.3px; }
    header span { font-size: 12px; color: var(--text-dim); margin-left: 2px; }

    /* ── Main layout ── */
    main {
      display: grid;
      grid-template-columns: 380px 1fr;
      gap: 0;
      height: calc(100vh - 65px);
      overflow: hidden;
    }

    /* ── Left panel ── */
    .panel-left {
      background: var(--surface);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      gap: 0;
      overflow-y: auto;
      padding: 24px;
    }

    .field-label {
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: var(--text-dim);
      margin-bottom: 8px;
    }

    .field-group { margin-bottom: 20px; }

    textarea, input[type="text"] {
      width: 100%;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      color: var(--text);
      font-family: var(--sans);
      font-size: 14px;
      padding: 12px 14px;
      outline: none;
      transition: border-color .15s;
      resize: vertical;
    }
    textarea:focus, input[type="text"]:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(110,231,183,.08);
    }
    textarea { min-height: 160px; line-height: 1.6; }

    /* Model badges */
    .agents {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-bottom: 20px;
    }
    .agent-row {
      display: flex;
      align-items: center;
      gap: 10px;
      background: var(--muted);
      border-radius: 8px;
      padding: 9px 12px;
      font-size: 12px;
    }
    .agent-dot {
      width: 8px; height: 8px; border-radius: 50%;
      flex-shrink: 0;
    }
    .dot-coder   { background: var(--accent); }
    .dot-reviewer{ background: var(--accent2); }
    .dot-tester  { background: var(--warn); }
    .agent-name { font-weight: 600; min-width: 72px; }
    .agent-model { color: var(--text-dim); font-family: var(--mono); font-size: 11px; }

    /* Ticket list */
    .tickets { display: flex; flex-direction: column; gap: 6px; }
    .ticket {
      background: var(--muted);
      border-radius: 8px;
      padding: 10px 12px;
      font-size: 12px;
      border-left: 3px solid var(--border);
      cursor: default;
    }
    .ticket.pending  { border-left-color: var(--text-dim); }
    .ticket.running  { border-left-color: var(--accent); animation: pulse 1.4s infinite; }
    .ticket.done     { border-left-color: #34d399; }
    .ticket.error    { border-left-color: var(--err); }
    .ticket-id   { font-family: var(--mono); color: var(--text-dim); font-size: 10px; }
    .ticket-title{ font-weight: 500; margin-top: 2px; }
    .ticket-status { font-size: 10px; margin-top: 4px; color: var(--text-dim); }

    @keyframes pulse {
      0%,100% { opacity:1 }
      50%      { opacity:.6 }
    }

    /* Btn */
    .btn {
      width: 100%;
      padding: 13px;
      background: var(--accent);
      color: var(--bg);
      border: none;
      border-radius: var(--radius);
      font-family: var(--sans);
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: opacity .15s, transform .1s;
      margin-top: auto;
    }
    .btn:hover:not(:disabled) { opacity: .88; }
    .btn:active:not(:disabled){ transform: scale(.98); }
    .btn:disabled { opacity: .4; cursor: not-allowed; }

    /* ── Right panel: terminal ── */
    .panel-right {
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .terminal-bar {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      font-size: 12px;
      color: var(--text-dim);
    }
    .terminal-bar .dot { width:10px;height:10px;border-radius:50%; }
    .dot-r{background:#f87171} .dot-y{background:#fbbf24} .dot-g{background:#34d399}

    #terminal {
      flex: 1;
      overflow-y: auto;
      padding: 20px 24px;
      font-family: var(--mono);
      font-size: 12.5px;
      line-height: 1.75;
      background: var(--bg);
    }
    .log-line { display: block; }
    .log-line.info    { color: var(--text); }
    .log-line.success { color: var(--accent); }
    .log-line.warn    { color: var(--warn); }
    .log-line.error   { color: var(--err); }
    .log-line.dim     { color: var(--text-dim); }
    .log-line.header  { color: var(--accent2); font-weight: 600; }
    .log-line.code    { color: #a5f3fc; }

    .progress-bar-wrap {
      height: 3px;
      background: var(--muted);
    }
    #progress-bar {
      height: 100%;
      width: 0%;
      background: linear-gradient(90deg, var(--accent), var(--accent2));
      transition: width .4s ease;
    }
  </style>
</head>
<body>

<header>
  <div class="logo">&gt;_</div>
  <div>
    <h1>AI Dev Agent</h1>
    <span>Powered by Ollama · Local LLMs</span>
  </div>
</header>

<main>
  <!-- LEFT -->
  <div class="panel-left">

    <div class="field-group">
      <div class="field-label">Projektbeschreibung</div>
      <textarea id="project-desc" placeholder="Beschreibe dein Projekt... z.B. &#10;&#10;Erstelle eine PHP REST-API mit Endpunkten für User-CRUD. Nutze PDO mit SQLite. Keine Frameworks."></textarea>
    </div>

    <div class="field-group">
      <div class="field-label">Git Repository URL</div>
      <input type="text" id="repo-url" placeholder="https://github.com/user/repo.git">
    </div>

    <div class="field-group">
      <div class="field-label">Git Branch</div>
      <input type="text" id="git-branch" value="main" placeholder="main">
    </div>

    <div class="field-group">
      <div class="field-label">Ollama URL</div>
      <input type="text" id="ollama-url" value="http://localhost:11434" placeholder="http://localhost:11434">
    </div>

    <div class="field-group">
      <div class="field-label">Agents (8–12 GB VRAM)</div>
      <div class="agents">
        <div class="agent-row"><div class="agent-dot dot-coder"></div><span class="agent-name">Coder</span><span class="agent-model">qwen2.5-coder:7b</span></div>
        <div class="agent-row"><div class="agent-dot dot-reviewer"></div><span class="agent-name">Reviewer</span><span class="agent-model">qwen2.5-coder:7b</span></div>
        <div class="agent-row"><div class="agent-dot dot-tester"></div><span class="agent-name">Tester</span><span class="agent-model">llama3.1:8b</span></div>
      </div>
    </div>

    <div class="field-group">
      <div class="field-label">Tickets <span id="ticket-count" style="font-weight:400;text-transform:none;letter-spacing:0"></span></div>
      <div class="tickets" id="ticket-list">
        <div style="color:var(--text-dim);font-size:12px">Noch keine Tickets – Projekt starten um zu beginnen.</div>
      </div>
    </div>

    <button class="btn" id="start-btn" onclick="startPipeline()">▶ Pipeline starten</button>
  </div>

  <!-- RIGHT: Terminal -->
  <div class="panel-right">
    <div class="terminal-bar">
      <div class="dot dot-r"></div>
      <div class="dot dot-y"></div>
      <div class="dot dot-g"></div>
      <span style="margin-left:8px">Pipeline Output</span>
    </div>
    <div class="progress-bar-wrap"><div id="progress-bar"></div></div>
    <div id="terminal">
      <span class="log-line dim">// Warte auf Projekt-Input...</span>
    </div>
  </div>
</main>

<script>
const term = document.getElementById('terminal');
const progressBar = document.getElementById('progress-bar');
let tickets = [];

function log(msg, cls = 'info') {
  const line = document.createElement('span');
  line.className = `log-line ${cls}`;
  line.textContent = msg;
  term.appendChild(line);
  term.appendChild(document.createElement('br'));
  term.scrollTop = term.scrollHeight;
}

function clearTerminal() {
  term.innerHTML = '';
}

function setProgress(pct) {
  progressBar.style.width = pct + '%';
}

function renderTickets(list) {
  const el = document.getElementById('ticket-list');
  document.getElementById('ticket-count').textContent = `(${list.length})`;
  el.innerHTML = list.map((t, i) => `
    <div class="ticket ${t.status}" id="ticket-${i}">
      <div class="ticket-id">TICKET-${String(i+1).padStart(3,'0')}</div>
      <div class="ticket-title">${t.title}</div>
      <div class="ticket-status">${statusLabel(t.status)}</div>
    </div>
  `).join('');
}

function statusLabel(s) {
  return {pending:'⏳ Ausstehend', running:'⚡ In Arbeit', done:'✅ Erledigt', error:'❌ Fehler'}[s] || s;
}

function updateTicket(idx, status) {
  tickets[idx].status = status;
  const el = document.getElementById(`ticket-${idx}`);
  if (el) {
    el.className = `ticket ${status}`;
    el.querySelector('.ticket-status').textContent = statusLabel(status);
  }
}

async function startPipeline() {
  const desc = document.getElementById('project-desc').value.trim();
  const repo = document.getElementById('repo-url').value.trim();
  const branch = document.getElementById('git-branch').value.trim() || 'main';
  const ollama = document.getElementById('ollama-url').value.trim();

  if (!desc) { alert('Bitte Projektbeschreibung eingeben.'); return; }
  if (!repo)  { alert('Bitte Repository-URL angeben.'); return; }

  document.getElementById('start-btn').disabled = true;
  clearTerminal();
  setProgress(0);
  tickets = [];

  //const params = new URLSearchParams({ desc, repo, branch, ollama });
  //const evtSource = new EventSource(`../api/pipeline.php?${params}`);
    const ollamaInternal = ollama.replace('localhost', 'ollama');
    const params = new URLSearchParams({ desc, repo, branch, ollama: ollamaInternal });
    const evtSource = new EventSource(`/api/pipeline.php?${params}`);

  evtSource.onmessage = (e) => {
    const data = JSON.parse(e.data);

    if (data.type === 'log') {
      log(data.msg, data.cls || 'info');
    }
    else if (data.type === 'tickets') {
      tickets = data.tickets.map(t => ({ title: t, status: 'pending' }));
      renderTickets(tickets);
      log(`\n📋 ${tickets.length} Tickets generiert.`, 'success');
    }
    else if (data.type === 'ticket_start') {
      updateTicket(data.idx, 'running');
    }
    else if (data.type === 'ticket_done') {
      updateTicket(data.idx, 'done');
      setProgress(((data.idx + 1) / tickets.length) * 90);
    }
    else if (data.type === 'ticket_error') {
      updateTicket(data.idx, 'error');
    }
    else if (data.type === 'done') {
      log('\n🎉 Pipeline abgeschlossen! Code wurde gepusht.', 'success');
      setProgress(100);
      document.getElementById('start-btn').disabled = false;
      evtSource.close();
    }
    else if (data.type === 'error') {
      log('\n❌ Fehler: ' + data.msg, 'error');
      document.getElementById('start-btn').disabled = false;
      evtSource.close();
    }
  };

  evtSource.onerror = () => {
    log('\n⚠ Verbindung unterbrochen.', 'warn');
    document.getElementById('start-btn').disabled = false;
    evtSource.close();
  };
}
</script>
</body>
</html>
