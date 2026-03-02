<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Tester — QuickSale POS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #0f172a;
            color: #e2e8f0;
            font-family: 'Segoe UI', monospace;
            min-height: 100vh;
            padding: 32px 20px;
        }

        h1 { font-size: 1.2rem; color: #22c55e; margin-bottom: 4px; }
        .subtitle { font-size: 0.8rem; color: #64748b; margin-bottom: 28px; }

        .layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1100px; margin: 0 auto; }

        .panel {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            overflow: hidden;
        }
        .panel-header {
            padding: 12px 18px;
            background: #162032;
            border-bottom: 1px solid #334155;
            display: flex; align-items: center; gap: 10px;
        }
        .panel-header h2 { font-size: 0.85rem; font-weight: 700; }
        .step-badge {
            width: 22px; height: 22px;
            border-radius: 50%;
            background: #22c55e;
            color: #0f172a;
            font-size: 0.7rem;
            font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .step-badge.blue { background: #38bdf8; }
        .panel-body { padding: 18px; }

        .form-group { margin-bottom: 14px; }
        .form-group label {
            display: block;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: #94a3b8;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 9px 12px;
            color: #e2e8f0;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            outline: none;
            transition: border-color .15s;
        }
        .form-group input:focus,
        .form-group select:focus { border-color: #22c55e; }
        .form-group select option { background: #1e293b; }

        .btn {
            width: 100%;
            padding: 11px;
            border-radius: 7px;
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            font-family: inherit;
            transition: all .15s;
            margin-top: 4px;
        }
        .btn-green { background: #22c55e; color: #0f172a; }
        .btn-green:hover { background: #16a34a; }
        .btn-blue  { background: #38bdf8; color: #0f172a; }
        .btn-blue:hover  { background: #0ea5e9; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }

        /* Status bar */
        .status-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            margin-bottom: 14px;
        }
        .status-bar.idle     { background: #1e293b; border: 1px solid #334155; color: #64748b; }
        .status-bar.loading  { background: #1c2d3f; border: 1px solid #38bdf8; color: #38bdf8; }
        .status-bar.success  { background: #052e16; border: 1px solid #22c55e; color: #4ade80; }
        .status-bar.error    { background: #2d0a14; border: 1px solid #f87171; color: #f87171; }
        .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .dot.idle    { background: #334155; }
        .dot.loading { background: #38bdf8; animation: pulse 1s infinite; }
        .dot.success { background: #22c55e; }
        .dot.error   { background: #f87171; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

        /* Response box */
        .response-box {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 14px;
            min-height: 120px;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            line-height: 1.6;
            white-space: pre-wrap;
            word-break: break-all;
            color: #94a3b8;
        }
        .response-box .key   { color: #38bdf8; }
        .response-box .str   { color: #4ade80; }
        .response-box .bool-true  { color: #4ade80; }
        .response-box .bool-false { color: #f87171; }
        .response-box .num   { color: #fb923c; }

        /* HTTP meta */
        .http-meta {
            display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap;
        }
        .http-chip {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
        }
        .chip-method { background: #1d4ed8; color: #bfdbfe; }
        .chip-200  { background: #052e16; color: #4ade80; }
        .chip-201  { background: #052e16; color: #4ade80; }
        .chip-400  { background: #2d1f06; color: #fbbf24; }
        .chip-401  { background: #2d0a14; color: #f87171; }
        .chip-403  { background: #2d0a14; color: #f87171; }
        .chip-404  { background: #2d0a14; color: #f87171; }
        .chip-405  { background: #2d0a14; color: #f87171; }
        .chip-409  { background: #2d0a14; color: #f87171; }
        .chip-500  { background: #2d0a14; color: #f87171; }
        .chip-time { background: #1e293b; color: #64748b; border: 1px solid #334155; }

        /* Session state */
        .session-state {
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 0.78rem;
            margin-bottom: 14px;
            background: #0f172a;
            border: 1px dashed #334155;
        }
        .session-state.active { border-color: #22c55e; }
        .session-state .label { color: #64748b; margin-bottom: 4px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: .5px; }
        .session-state .value { color: #e2e8f0; font-weight: 600; }
        .session-state .value span { color: #22c55e; }

        /* Divider */
        .divider { text-align: center; color: #334155; font-size: 0.7rem; margin: 4px 0 12px; text-transform: uppercase; letter-spacing: 1px; }

        /* Payload preview */
        .payload-preview {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 10px 12px;
            font-family: 'Courier New', monospace;
            font-size: 0.78rem;
            color: #64748b;
            margin-bottom: 14px;
            line-height: 1.6;
        }
        .payload-preview span { color: #38bdf8; }
    </style>
</head>
<body>

<div style="max-width:1100px;margin:0 auto;">
    <h1>🧪 QuickSale API Tester</h1>
    <p class="subtitle">Tests add-users.php — logs in first to get a valid session, then sends the add-user request.</p>

    <div class="layout">

        <!-- ── LEFT: Step 1 Login + Step 2 Add User ── -->
        <div style="display:flex;flex-direction:column;gap:16px;">

            <!-- STEP 1: Login -->
            <div class="panel">
                <div class="panel-header">
                    <div class="step-badge">1</div>
                    <h2>Login First (Get Session)</h2>
                </div>
                <div class="panel-body">
                    <div id="sessionState" class="session-state">
                        <div class="label">Session Status</div>
                        <div class="value" id="sessionText">⭕ Not logged in</div>
                    </div>

                    <div class="form-group">
                        <label>Login URL</label>
                        <input type="text" id="loginUrl" value="http://localhost/POS-System/backend/api/auth.php?action=login">
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="loginUsername" value="admin">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="loginPassword" value="admin123">
                    </div>

                    <button class="btn btn-green" id="loginBtn" onclick="doLogin()">🔐 Login</button>
                </div>
            </div>

            <!-- STEP 2: Add User -->
            <div class="panel">
                <div class="panel-header">
                    <div class="step-badge blue">2</div>
                    <h2>Add User (Test Payload)</h2>
                </div>
                <div class="panel-body">
                    <div class="form-group">
                        <label>API URL</label>
                        <input type="text" id="addUrl" value="http://localhost/POS-System/backend/api/users/add-users.php">
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="f_full_name" value="Test User" oninput="updatePreview()">
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="f_username" value="testuser" oninput="updatePreview()">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select id="f_role" onchange="updatePreview()">
                            <option value="cashier">cashier</option>
                            <option value="manager">manager</option>
                            <option value="admin">admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" id="f_password" value="password123" oninput="updatePreview()">
                    </div>

                    <div class="divider">— JSON Payload Preview —</div>
                    <div class="payload-preview" id="payloadPreview"></div>

                    <button class="btn btn-blue" id="addBtn" onclick="doAddUser()">🚀 Send POST Request</button>
                </div>
            </div>

        </div>

        <!-- ── RIGHT: Responses ── -->
        <div style="display:flex;flex-direction:column;gap:16px;">

            <!-- Login Response -->
            <div class="panel">
                <div class="panel-header">
                    <div class="step-badge">1</div>
                    <h2>Login Response</h2>
                </div>
                <div class="panel-body">
                    <div id="loginStatus" class="status-bar idle">
                        <div class="dot idle" id="loginDot"></div>
                        <span id="loginStatusText">Waiting...</span>
                    </div>
                    <div id="loginHttpMeta" class="http-meta"></div>
                    <div id="loginResponse" class="response-box">// Login response will appear here</div>
                </div>
            </div>

            <!-- Add User Response -->
            <div class="panel">
                <div class="panel-header">
                    <div class="step-badge blue">2</div>
                    <h2>Add User Response</h2>
                </div>
                <div class="panel-body">
                    <div id="addStatus" class="status-bar idle">
                        <div class="dot idle" id="addDot"></div>
                        <span id="addStatusText">Waiting for Step 1...</span>
                    </div>
                    <div id="addHttpMeta" class="http-meta"></div>
                    <div id="addResponse" class="response-box">// Add user response will appear here</div>
                </div>
            </div>

        </div>

    </div><!-- /layout -->
</div>

<script>
// ── Track login state ──
let isLoggedIn = false;

// ── Update payload preview as user types ──
function updatePreview() {
    const payload = {
        full_name: document.getElementById('f_full_name').value,
        username:  document.getElementById('f_username').value,
        role:      document.getElementById('f_role').value,
        password:  document.getElementById('f_password').value
    };
    document.getElementById('payloadPreview').innerHTML =
        syntaxHighlight(JSON.stringify(payload, null, 2));
}

// ── Syntax highlighter for JSON ──
function syntaxHighlight(json) {
    return json
        .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, match => {
            if (/^"/.test(match)) {
                if (/:$/.test(match)) return `<span class="key">${match}</span>`;
                return `<span class="str">${match}</span>`;
            }
            if (/true/.test(match))  return `<span class="bool-true">${match}</span>`;
            if (/false/.test(match)) return `<span class="bool-false">${match}</span>`;
            return `<span class="num">${match}</span>`;
        });
}

// ── Set status bar state ──
function setStatus(prefix, state, text) {
    const bar  = document.getElementById(prefix + 'Status');
    const dot  = document.getElementById(prefix + 'Dot');
    const span = document.getElementById(prefix + 'StatusText');
    bar.className  = 'status-bar ' + state;
    dot.className  = 'dot ' + state;
    span.textContent = text;
}

// ── Render HTTP meta chips ──
function setHttpMeta(prefix, method, status, ms) {
    const chip = `<span class="http-chip chip-method">${method}</span>
                  <span class="http-chip chip-${status}">HTTP ${status}</span>
                  <span class="http-chip chip-time">⏱ ${ms}ms</span>`;
    document.getElementById(prefix + 'HttpMeta').innerHTML = chip;
}

// ── STEP 1: Login ──
async function doLogin() {
    const url      = document.getElementById('loginUrl').value.trim();
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value;

    if (!url || !username || !password) {
        setStatus('login', 'error', 'Fill in all login fields.');
        return;
    }

    setStatus('login', 'loading', 'Sending login request...');
    document.getElementById('loginBtn').disabled = true;
    document.getElementById('loginHttpMeta').innerHTML = '';
    document.getElementById('loginResponse').textContent = '// Loading...';

    const start = Date.now();
    try {
        // Use FormData so auth.php receives $_POST values
        const form = new FormData();
        form.append('username', username);
        form.append('password', password);

        const res  = await fetch(url, { method: 'POST', body: form, credentials: 'include' });
        const ms   = Date.now() - start;
        const text = await res.text();

        setHttpMeta('login', 'POST', res.status, ms);

        let parsed;
        try { parsed = JSON.parse(text); } catch { parsed = null; }

        document.getElementById('loginResponse').innerHTML =
            parsed ? syntaxHighlight(JSON.stringify(parsed, null, 2)) : text;

        if (res.ok && parsed?.success) {
            isLoggedIn = true;
            setStatus('login', 'success', '✅ Logged in successfully! Session active.');
            const sessionEl = document.getElementById('sessionState');
            sessionEl.classList.add('active');
            document.getElementById('sessionText').innerHTML =
                `✅ Logged in as <span>${username}</span> · Role: <span>${parsed.role ?? 'unknown'}</span>`;
            setStatus('add', 'idle', 'Ready — click "Send POST Request"');
        } else {
            isLoggedIn = false;
            setStatus('login', 'error', '❌ Login failed. Check credentials.');
        }

    } catch (err) {
        const ms = Date.now() - start;
        setStatus('login', 'error', '❌ Network error: ' + err.message);
        document.getElementById('loginResponse').textContent = 'Error: ' + err.message;
    } finally {
        document.getElementById('loginBtn').disabled = false;
    }
}

// ── STEP 2: Add User ──
async function doAddUser() {
    if (!isLoggedIn) {
        setStatus('add', 'error', '❌ Login first (Step 1).');
        return;
    }

    const url = document.getElementById('addUrl').value.trim();
    const payload = {
        full_name: document.getElementById('f_full_name').value.trim(),
        username:  document.getElementById('f_username').value.trim(),
        role:      document.getElementById('f_role').value,
        password:  document.getElementById('f_password').value
    };

    if (!payload.full_name || !payload.username || !payload.password) {
        setStatus('add', 'error', '❌ Full name, username, and password are required.');
        return;
    }

    setStatus('add', 'loading', 'Sending add-user request...');
    document.getElementById('addBtn').disabled = true;
    document.getElementById('addHttpMeta').innerHTML = '';
    document.getElementById('addResponse').textContent = '// Loading...';

    const start = Date.now();
    try {
        const res  = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            credentials: 'include'  // ← sends the session cookie
        });
        const ms   = Date.now() - start;
        const text = await res.text();

        setHttpMeta('add', 'POST', res.status, ms);

        let parsed;
        try { parsed = JSON.parse(text); } catch { parsed = null; }

        document.getElementById('addResponse').innerHTML =
            parsed ? syntaxHighlight(JSON.stringify(parsed, null, 2)) : text;

        if (res.ok && parsed?.success) {
            setStatus('add', 'success', '✅ User added! ID: ' + (parsed.user_id ?? '?'));
        } else {
            setStatus('add', 'error', '❌ ' + (parsed?.message ?? 'Request failed.'));
        }

    } catch (err) {
        setStatus('add', 'error', '❌ Network error: ' + err.message);
        document.getElementById('addResponse').textContent = 'Error: ' + err.message;
    } finally {
        document.getElementById('addBtn').disabled = false;
    }
}

// Init preview on load
updatePreview();
</script>
</body>
</html>