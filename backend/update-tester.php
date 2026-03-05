<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update User Tester — QuickSale POS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #0f172a;
            color: #e2e8f0;
            font-family: 'Segoe UI', monospace;
            min-height: 100vh;
            padding: 32px 20px;
        }

        .top-bar {
            max-width: 1200px;
            margin: 0 auto 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .top-bar h1 { font-size: 1.2rem; color: #38bdf8; }
        .top-bar h1 span { color: #e2e8f0; font-weight: 400; font-size: 0.85rem; margin-left: 10px; }
        .subtitle { font-size: 0.78rem; color: #64748b; margin-top: 3px; }

        .nav-links { display: flex; gap: 10px; }
        .nav-link {
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            background: #1e293b;
            color: #64748b;
            border: 1px solid #334155;
            transition: all .15s;
        }
        .nav-link:hover { color: #e2e8f0; border-color: #475569; }
        .nav-link.active { background: #1e3a5f; color: #38bdf8; border-color: #38bdf8; }

        .layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* ── Panels ── */
        .panel { background: #1e293b; border: 1px solid #334155; border-radius: 10px; overflow: hidden; }
        .panel-header {
            padding: 12px 18px;
            background: #162032;
            border-bottom: 1px solid #334155;
            display: flex; align-items: center; gap: 10px;
        }
        .panel-header h2 { font-size: 0.85rem; font-weight: 700; }
        .step-badge {
            width: 22px; height: 22px; border-radius: 50%;
            font-size: 0.7rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .sb-green  { background: #22c55e; color: #0f172a; }
        .sb-blue   { background: #38bdf8; color: #0f172a; }
        .sb-orange { background: #fb923c; color: #0f172a; }
        .panel-body { padding: 18px; }

        /* ── Forms ── */
        .form-group { margin-bottom: 13px; }
        .form-group label {
            display: block; font-size: 0.7rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .6px;
            color: #94a3b8; margin-bottom: 5px;
        }
        .form-group input,
        .form-group select {
            width: 100%; background: #0f172a; border: 1px solid #334155;
            border-radius: 6px; padding: 9px 12px; color: #e2e8f0;
            font-family: 'Courier New', monospace; font-size: 0.85rem;
            outline: none; transition: border-color .15s;
        }
        .form-group input:focus,
        .form-group select:focus { border-color: #38bdf8; }
        .form-group select option { background: #1e293b; }
        .hint { font-size: 0.7rem; color: #475569; margin-top: 4px; }

        /* ── Mode tabs ── */
        .mode-tabs { display: flex; gap: 8px; margin-bottom: 16px; }
        .mode-tab {
            flex: 1; padding: 8px; border-radius: 7px; font-size: 0.8rem;
            font-weight: 700; cursor: pointer; border: 1px solid #334155;
            background: #0f172a; color: #64748b; font-family: inherit;
            transition: all .15s; text-align: center;
        }
        .mode-tab.active-full   { background: #1e3a5f; color: #38bdf8; border-color: #38bdf8; }
        .mode-tab.active-toggle { background: #2d1f06; color: #fb923c; border-color: #fb923c; }

        /* toggle section */
        .section { display: none; }
        .section.visible { display: block; }

        /* ── Buttons ── */
        .btn {
            width: 100%; padding: 11px; border-radius: 7px; font-size: 0.875rem;
            font-weight: 700; cursor: pointer; border: none; font-family: inherit;
            transition: all .15s; margin-top: 4px;
        }
        .btn-green  { background: #22c55e; color: #0f172a; }
        .btn-green:hover  { background: #16a34a; }
        .btn-blue   { background: #38bdf8; color: #0f172a; }
        .btn-blue:hover   { background: #0ea5e9; }
        .btn-orange { background: #fb923c; color: #0f172a; }
        .btn-orange:hover { background: #ea7f24; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }

        /* ── Status bars ── */
        .status-bar {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 12px; border-radius: 6px;
            font-size: 0.78rem; font-weight: 600; margin-bottom: 14px;
        }
        .status-bar.idle    { background: #1e293b; border: 1px solid #334155; color: #64748b; }
        .status-bar.loading { background: #1c2d3f; border: 1px solid #38bdf8; color: #38bdf8; }
        .status-bar.success { background: #052e16; border: 1px solid #22c55e; color: #4ade80; }
        .status-bar.error   { background: #2d0a14; border: 1px solid #f87171; color: #f87171; }
        .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .dot.idle    { background: #334155; }
        .dot.loading { background: #38bdf8; animation: pulse 1s infinite; }
        .dot.success { background: #22c55e; }
        .dot.error   { background: #f87171; }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

        /* ── Response box ── */
        .response-box {
            background: #0f172a; border: 1px solid #334155; border-radius: 6px;
            padding: 14px; min-height: 120px;
            font-family: 'Courier New', monospace; font-size: 0.8rem;
            line-height: 1.6; white-space: pre-wrap; word-break: break-all; color: #94a3b8;
        }
        .key        { color: #38bdf8; }
        .str        { color: #4ade80; }
        .bool-true  { color: #4ade80; }
        .bool-false { color: #f87171; }
        .num        { color: #fb923c; }

        /* ── HTTP chips ── */
        .http-meta { display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap; }
        .http-chip { padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
        .chip-POST   { background: #1d4ed8; color: #bfdbfe; }
        .chip-200, .chip-201 { background: #052e16; color: #4ade80; }
        .chip-400    { background: #2d1f06; color: #fbbf24; }
        .chip-401, .chip-403, .chip-404, .chip-405, .chip-409, .chip-500
                     { background: #2d0a14; color: #f87171; }
        .chip-time   { background: #1e293b; color: #64748b; border: 1px solid #334155; }

        /* ── Session state ── */
        .session-state {
            padding: 10px 14px; border-radius: 6px; font-size: 0.78rem;
            margin-bottom: 14px; background: #0f172a; border: 1px dashed #334155;
        }
        .session-state.active { border-color: #22c55e; }
        .session-state .s-label { color: #64748b; margin-bottom: 4px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: .5px; }
        .session-state .s-value { color: #e2e8f0; font-weight: 600; }
        .session-state .s-value span { color: #22c55e; }

        /* ── Payload preview ── */
        .divider { text-align: center; color: #334155; font-size: 0.7rem; margin: 4px 0 12px; text-transform: uppercase; letter-spacing: 1px; }
        .payload-preview {
            background: #0f172a; border: 1px solid #334155; border-radius: 6px;
            padding: 10px 12px; font-family: 'Courier New', monospace;
            font-size: 0.78rem; color: #64748b; margin-bottom: 14px; line-height: 1.6;
        }

        /* ── Quick reference ── */
        .ref-box {
            background: #0f172a; border: 1px solid #334155; border-radius: 6px;
            padding: 12px 14px; font-size: 0.78rem; line-height: 1.8; color: #64748b;
        }
        .ref-box strong { color: #e2e8f0; }
        .ref-box .tag {
            display: inline-block; padding: 1px 8px; border-radius: 4px;
            font-size: 0.7rem; font-weight: 700; margin-left: 4px;
        }
        .tag-req  { background: #2d1f06; color: #fbbf24; }
        .tag-opt  { background: #1e293b; color: #64748b; border: 1px solid #334155; }
        .tag-mode { background: #1e3a5f; color: #38bdf8; }
    </style>
</head>
<body>

<div class="top-bar">
    <div>
        <h1>🧪 QuickSale API Tester <span>/ update-users.php</span></h1>
        <div class="subtitle">Login first → then test Full Update or Toggle Status</div>
    </div>
    <div class="nav-links">
        <a href="api-tester.html"        class="nav-link">➕ Add User</a>
        <a href="update-tester.html"     class="nav-link active">✏️ Update User</a>
        <a href="delete-tester.html"     class="nav-link">🗑️ Delete User</a>
    </div>
</div>

<div class="layout">

    <!-- ══ LEFT COLUMN ══ -->
    <div style="display:flex;flex-direction:column;gap:16px;">

        <!-- STEP 1: Login -->
        <div class="panel">
            <div class="panel-header">
                <div class="step-badge sb-green">1</div>
                <h2>Login First (Get Session)</h2>
            </div>
            <div class="panel-body">
                <div id="sessionState" class="session-state">
                    <div class="s-label">Session Status</div>
                    <div class="s-value" id="sessionText">⭕ Not logged in</div>
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

        <!-- STEP 2: Update -->
        <div class="panel">
            <div class="panel-header">
                <div class="step-badge sb-blue">2</div>
                <h2>Update User</h2>
            </div>
            <div class="panel-body">

                <div class="form-group">
                    <label>API URL</label>
                    <input type="text" id="updateUrl" value="http://localhost/POS-System/backend/api/users/update-users.php">
                </div>

                <!-- Mode selector -->
                <div class="mode-tabs">
                    <button class="mode-tab active-full" id="tab-full"   onclick="setMode('full')">✏️ Full Update</button>
                    <button class="mode-tab"             id="tab-toggle" onclick="setMode('toggle')">🔄 Toggle Status Only</button>
                </div>

                <!-- FULL UPDATE fields -->
                <div id="section-full" class="section visible">
                    <div class="form-group">
                        <label>User ID <span style="color:#f87171">*</span></label>
                        <input type="number" id="f_id" value="2" min="1" oninput="updatePreview()">
                        <div class="hint">The ID of the user you want to update (check phpMyAdmin)</div>
                    </div>
                    <div class="form-group">
                        <label>Full Name <span style="color:#f87171">*</span></label>
                        <input type="text" id="f_full_name" value="Juan dela Cruz Updated" oninput="updatePreview()">
                    </div>
                    <div class="form-group">
                        <label>Username <span style="color:#f87171">*</span></label>
                        <input type="text" id="f_username" value="cashier1" oninput="updatePreview()">
                    </div>
                    <div class="form-group">
                        <label>Role <span style="color:#f87171">*</span></label>
                        <select id="f_role" onchange="updatePreview()">
                            <option value="cashier">cashier</option>
                            <option value="manager">manager</option>
                            <option value="admin">admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status <span style="color:#f87171">*</span></label>
                        <select id="f_status" onchange="updatePreview()">
                            <option value="active">active</option>
                            <option value="inactive">inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>New Password <span style="color:#64748b">(optional)</span></label>
                        <input type="password" id="f_password" placeholder="Leave blank to keep current" oninput="updatePreview()">
                        <div class="hint">Leave blank = password stays the same</div>
                    </div>
                    <div class="divider">— JSON Payload Preview —</div>
                    <div class="payload-preview" id="payloadPreview"></div>
                    <button class="btn btn-blue" id="updateBtn" onclick="doUpdate()">🚀 Send Full Update</button>
                </div>

                <!-- TOGGLE STATUS fields -->
                <div id="section-toggle" class="section">
                    <div class="form-group">
                        <label>User ID <span style="color:#f87171">*</span></label>
                        <input type="number" id="t_id" value="2" min="1" oninput="updateTogglePreview()">
                    </div>
                    <div class="form-group">
                        <label>New Status <span style="color:#f87171">*</span></label>
                        <select id="t_status" onchange="updateTogglePreview()">
                            <option value="inactive">inactive (deactivate)</option>
                            <option value="active">active (reactivate)</option>
                        </select>
                    </div>
                    <div class="divider">— JSON Payload Preview —</div>
                    <div class="payload-preview" id="togglePreview"></div>
                    <button class="btn btn-orange" id="toggleBtn" onclick="doToggle()">🔄 Send Toggle Request</button>
                </div>

            </div>
        </div>

    </div><!-- /left -->

    <!-- ══ RIGHT COLUMN ══ -->
    <div style="display:flex;flex-direction:column;gap:16px;">

        <!-- Login Response -->
        <div class="panel">
            <div class="panel-header">
                <div class="step-badge sb-green">1</div>
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

        <!-- Update Response -->
        <div class="panel">
            <div class="panel-header">
                <div class="step-badge sb-blue">2</div>
                <h2>Update Response</h2>
            </div>
            <div class="panel-body">
                <div id="updateStatus" class="status-bar idle">
                    <div class="dot idle" id="updateDot"></div>
                    <span id="updateStatusText">Waiting for Step 1...</span>
                </div>
                <div id="updateHttpMeta" class="http-meta"></div>
                <div id="updateResponse" class="response-box">// Update response will appear here</div>
            </div>
        </div>

        <!-- Quick Reference -->
        <div class="panel">
            <div class="panel-header">
                <div class="step-badge" style="background:#475569;color:#e2e8f0;">?</div>
                <h2>Quick Reference</h2>
            </div>
            <div class="panel-body">
                <div class="ref-box">
                    <strong>Full Update fields:</strong><br>
                    <code>id</code> <span class="tag tag-req">required</span><br>
                    <code>full_name</code> <span class="tag tag-req">required</span><br>
                    <code>username</code> <span class="tag tag-req">required</span><br>
                    <code>role</code> <span class="tag tag-req">required</span> — admin / manager / cashier<br>
                    <code>status</code> <span class="tag tag-req">required</span> — active / inactive<br>
                    <code>password</code> <span class="tag tag-opt">optional</span> — blank = no change<br><br>

                    <strong>Toggle Status fields:</strong><br>
                    <code>id</code> <span class="tag tag-req">required</span><br>
                    <code>status</code> <span class="tag tag-req">required</span><br>
                    <code>toggle_only</code> <span class="tag tag-mode">true</span> — tells API to skip other fields<br><br>

                    <strong>Sample users in DB:</strong><br>
                    ID 1 → admin &nbsp;|&nbsp; ID 2 → cashier1 &nbsp;|&nbsp; ID 3 → manager1
                </div>
            </div>
        </div>

    </div><!-- /right -->

</div><!-- /layout -->

<script>
let isLoggedIn = false;
let currentMode = 'full';

// ── Mode switching ──
function setMode(mode) {
    currentMode = mode;
    document.getElementById('section-full').classList.toggle('visible', mode === 'full');
    document.getElementById('section-toggle').classList.toggle('visible', mode === 'toggle');
    document.getElementById('tab-full').className   = 'mode-tab' + (mode === 'full'   ? ' active-full'   : '');
    document.getElementById('tab-toggle').className = 'mode-tab' + (mode === 'toggle' ? ' active-toggle' : '');
    if (mode === 'toggle') updateTogglePreview();
    else updatePreview();
}

// ── Syntax highlight ──
function syntaxHighlight(json) {
    return json.replace(
        /("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g,
        match => {
            if (/^"/.test(match)) {
                if (/:$/.test(match)) return `<span class="key">${match}</span>`;
                return `<span class="str">${match}</span>`;
            }
            if (/true/.test(match))  return `<span class="bool-true">${match}</span>`;
            if (/false/.test(match)) return `<span class="bool-false">${match}</span>`;
            return `<span class="num">${match}</span>`;
        }
    );
}

// ── Status bar ──
function setStatus(prefix, state, text) {
    document.getElementById(prefix + 'Status').className    = 'status-bar ' + state;
    document.getElementById(prefix + 'Dot').className       = 'dot ' + state;
    document.getElementById(prefix + 'StatusText').textContent = text;
}

// ── HTTP meta chips ──
function setHttpMeta(prefix, method, status, ms) {
    document.getElementById(prefix + 'HttpMeta').innerHTML =
        `<span class="http-chip chip-${method}">${method}</span>
         <span class="http-chip chip-${status}">HTTP ${status}</span>
         <span class="http-chip chip-time">⏱ ${ms}ms</span>`;
}

// ── Payload previews ──
function updatePreview() {
    const payload = {
        id:        parseInt(document.getElementById('f_id').value) || 0,
        full_name: document.getElementById('f_full_name').value,
        username:  document.getElementById('f_username').value,
        role:      document.getElementById('f_role').value,
        status:    document.getElementById('f_status').value,
    };
    const pw = document.getElementById('f_password').value;
    if (pw) payload.password = pw;

    document.getElementById('payloadPreview').innerHTML =
        syntaxHighlight(JSON.stringify(payload, null, 2));
}

function updateTogglePreview() {
    const payload = {
        id:          parseInt(document.getElementById('t_id').value) || 0,
        status:      document.getElementById('t_status').value,
        toggle_only: true
    };
    document.getElementById('togglePreview').innerHTML =
        syntaxHighlight(JSON.stringify(payload, null, 2));
}

// ── STEP 1: Login ──
async function doLogin() {
    const url      = document.getElementById('loginUrl').value.trim();
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value;

    if (!url || !username || !password) {
        setStatus('login', 'error', 'Fill in all login fields.'); return;
    }

    setStatus('login', 'loading', 'Sending login request...');
    document.getElementById('loginBtn').disabled = true;
    document.getElementById('loginHttpMeta').innerHTML = '';
    document.getElementById('loginResponse').textContent = '// Loading...';

    const start = Date.now();
    try {
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
            setStatus('login', 'success', '✅ Logged in! Session active.');
            const el = document.getElementById('sessionState');
            el.classList.add('active');
            document.getElementById('sessionText').innerHTML =
                `✅ Logged in as <span>${username}</span> · Role: <span>${parsed.role ?? 'unknown'}</span>`;
            setStatus('update', 'idle', 'Ready — choose a mode and send');
        } else {
            isLoggedIn = false;
            setStatus('login', 'error', '❌ Login failed. Check credentials.');
        }
    } catch (err) {
        setStatus('login', 'error', '❌ Network error: ' + err.message);
        document.getElementById('loginResponse').textContent = 'Error: ' + err.message;
    } finally {
        document.getElementById('loginBtn').disabled = false;
    }
}

// ── STEP 2a: Full Update ──
async function doUpdate() {
    if (!isLoggedIn) { setStatus('update', 'error', '❌ Login first (Step 1).'); return; }

    const url = document.getElementById('updateUrl').value.trim();
    const payload = {
        id:        parseInt(document.getElementById('f_id').value) || 0,
        full_name: document.getElementById('f_full_name').value.trim(),
        username:  document.getElementById('f_username').value.trim(),
        role:      document.getElementById('f_role').value,
        status:    document.getElementById('f_status').value,
    };
    const pw = document.getElementById('f_password').value;
    if (pw) payload.password = pw;

    if (!payload.id || !payload.full_name || !payload.username) {
        setStatus('update', 'error', '❌ ID, full name, and username are required.'); return;
    }

    await sendRequest(url, payload, 'updateBtn');
}

// ── STEP 2b: Toggle Status ──
async function doToggle() {
    if (!isLoggedIn) { setStatus('update', 'error', '❌ Login first (Step 1).'); return; }

    const url = document.getElementById('updateUrl').value.trim();
    const payload = {
        id:          parseInt(document.getElementById('t_id').value) || 0,
        status:      document.getElementById('t_status').value,
        toggle_only: true
    };

    if (!payload.id) {
        setStatus('update', 'error', '❌ User ID is required.'); return;
    }

    await sendRequest(url, payload, 'toggleBtn');
}

// ── Shared fetch helper ──
async function sendRequest(url, payload, btnId) {
    setStatus('update', 'loading', 'Sending request...');
    document.getElementById(btnId).disabled = true;
    document.getElementById('updateHttpMeta').innerHTML = '';
    document.getElementById('updateResponse').textContent = '// Loading...';

    const start = Date.now();
    try {
        const res  = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
            credentials: 'include'
        });
        const ms   = Date.now() - start;
        const text = await res.text();

        setHttpMeta('update', 'POST', res.status, ms);

        let parsed;
        try { parsed = JSON.parse(text); } catch { parsed = null; }

        document.getElementById('updateResponse').innerHTML =
            parsed ? syntaxHighlight(JSON.stringify(parsed, null, 2)) : text;

        if (res.ok && parsed?.success) {
            setStatus('update', 'success', '✅ ' + (parsed.message ?? 'Update successful!'));
        } else {
            setStatus('update', 'error', '❌ ' + (parsed?.message ?? parsed?.error ?? 'Request failed.'));
        }
    } catch (err) {
        setStatus('update', 'error', '❌ Network error: ' + err.message);
        document.getElementById('updateResponse').textContent = 'Error: ' + err.message;
    } finally {
        document.getElementById(btnId).disabled = false;
    }
}

// Init
updatePreview();
updateTogglePreview();
</script>
</body>
</html>