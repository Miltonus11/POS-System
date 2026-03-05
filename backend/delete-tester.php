<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User Tester — QuickSale POS</title>
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
        .top-bar h1 { font-size: 1.2rem; color: #f87171; }
        .top-bar h1 span { color: #e2e8f0; font-weight: 400; font-size: 0.85rem; margin-left: 10px; }
        .subtitle { font-size: 0.78rem; color: #64748b; margin-top: 3px; }

        .nav-links { display: flex; gap: 10px; }
        .nav-link {
            padding: 6px 14px; border-radius: 6px; font-size: 0.78rem;
            font-weight: 600; text-decoration: none; background: #1e293b;
            color: #64748b; border: 1px solid #334155; transition: all .15s;
        }
        .nav-link:hover { color: #e2e8f0; border-color: #475569; }
        .nav-link.active { background: #2d0a14; color: #f87171; border-color: #f87171; }

        .layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .panel { background: #1e293b; border: 1px solid #334155; border-radius: 10px; overflow: hidden; }
        .panel-header {
            padding: 12px 18px; background: #162032;
            border-bottom: 1px solid #334155;
            display: flex; align-items: center; gap: 10px;
        }
        .panel-header h2 { font-size: 0.85rem; font-weight: 700; }
        .step-badge {
            width: 22px; height: 22px; border-radius: 50%;
            font-size: 0.7rem; font-weight: 800;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .sb-green { background: #22c55e; color: #0f172a; }
        .sb-red   { background: #f87171; color: #0f172a; }
        .panel-body { padding: 18px; }

        .form-group { margin-bottom: 13px; }
        .form-group label {
            display: block; font-size: 0.7rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .6px;
            color: #94a3b8; margin-bottom: 5px;
        }
        .form-group input, .form-group select {
            width: 100%; background: #0f172a; border: 1px solid #334155;
            border-radius: 6px; padding: 9px 12px; color: #e2e8f0;
            font-family: 'Courier New', monospace; font-size: 0.85rem;
            outline: none; transition: border-color .15s;
        }
        .form-group input:focus { border-color: #f87171; }
        .hint { font-size: 0.7rem; color: #475569; margin-top: 4px; }

        .btn {
            width: 100%; padding: 11px; border-radius: 7px; font-size: 0.875rem;
            font-weight: 700; cursor: pointer; border: none; font-family: inherit;
            transition: all .15s; margin-top: 4px;
        }
        .btn-green { background: #22c55e; color: #0f172a; }
        .btn-green:hover { background: #16a34a; }
        .btn-red   { background: #ef4444; color: #fff; }
        .btn-red:hover   { background: #dc2626; }
        .btn:disabled { opacity: .5; cursor: not-allowed; }

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

        .http-meta { display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap; }
        .http-chip { padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
        .chip-POST { background: #1d4ed8; color: #bfdbfe; }
        .chip-200, .chip-201 { background: #052e16; color: #4ade80; }
        .chip-400  { background: #2d1f06; color: #fbbf24; }
        .chip-401, .chip-403, .chip-404, .chip-405, .chip-409, .chip-500
                   { background: #2d0a14; color: #f87171; }
        .chip-time { background: #1e293b; color: #64748b; border: 1px solid #334155; }

        .session-state {
            padding: 10px 14px; border-radius: 6px; font-size: 0.78rem;
            margin-bottom: 14px; background: #0f172a; border: 1px dashed #334155;
        }
        .session-state.active { border-color: #22c55e; }
        .session-state .s-label { color: #64748b; margin-bottom: 4px; font-size: 0.7rem; text-transform: uppercase; letter-spacing: .5px; }
        .session-state .s-value { color: #e2e8f0; font-weight: 600; }
        .session-state .s-value span { color: #22c55e; }

        .divider { text-align: center; color: #334155; font-size: 0.7rem; margin: 4px 0 12px; text-transform: uppercase; letter-spacing: 1px; }
        .payload-preview {
            background: #0f172a; border: 1px solid #334155; border-radius: 6px;
            padding: 10px 12px; font-family: 'Courier New', monospace;
            font-size: 0.78rem; color: #64748b; margin-bottom: 14px; line-height: 1.6;
        }

        /* ── Danger warning box ── */
        .danger-box {
            background: #1a0a0a;
            border: 1px solid #7f1d1d;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 16px;
            font-size: 0.78rem;
            line-height: 1.7;
            color: #fca5a5;
        }
        .danger-box strong { color: #f87171; display: block; margin-bottom: 4px; font-size: 0.82rem; }

        /* ── Safety rules box ── */
        .rules-box {
            background: #0f172a; border: 1px solid #334155; border-radius: 6px;
            padding: 12px 14px; font-size: 0.78rem; line-height: 1.9; color: #64748b;
        }
        .rules-box strong { color: #e2e8f0; }
        .rule-item { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 2px; }
        .rule-icon { flex-shrink: 0; }
        .rule-text { color: #94a3b8; }
        .rule-text b { color: #f87171; }

        /* ── Confirm overlay ── */
        .overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 100;
            align-items: center;
            justify-content: center;
        }
        .overlay.visible { display: flex; }
        .confirm-box {
            background: #1e293b;
            border: 1px solid #f87171;
            border-radius: 12px;
            padding: 28px 32px;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }
        .confirm-box h3 { color: #f87171; font-size: 1rem; margin-bottom: 10px; }
        .confirm-box p  { color: #94a3b8; font-size: 0.82rem; margin-bottom: 20px; line-height: 1.6; }
        .confirm-box .confirm-id {
            background: #0f172a; border: 1px solid #334155;
            border-radius: 6px; padding: 8px 14px;
            font-family: 'Courier New', monospace;
            color: #f87171; font-size: 0.9rem; font-weight: 700;
            margin-bottom: 20px; display: inline-block;
        }
        .confirm-btns { display: flex; gap: 10px; }
        .confirm-btns button {
            flex: 1; padding: 10px; border-radius: 7px; border: none;
            font-size: 0.875rem; font-weight: 700; cursor: pointer; font-family: inherit;
        }
        .btn-cancel { background: #334155; color: #e2e8f0; }
        .btn-cancel:hover { background: #475569; }
        .btn-confirm-del { background: #ef4444; color: #fff; }
        .btn-confirm-del:hover { background: #dc2626; }
    </style>
</head>
<body>

<!-- ── Confirm Delete Overlay ── -->
<div class="overlay" id="confirmOverlay">
    <div class="confirm-box">
        <h3>⚠️ Confirm Delete</h3>
        <p>You are about to delete this user. This action <strong>cannot be undone</strong>.<br>
           If the user has sales records, they will be <b>deactivated</b> instead.</p>
        <div class="confirm-id" id="confirmIdDisplay">User ID: ?</div>
        <div class="confirm-btns">
            <button class="btn-cancel" onclick="cancelDelete()">Cancel</button>
            <button class="btn-confirm-del" onclick="confirmDelete()">Yes, Delete</button>
        </div>
    </div>
</div>

<div class="top-bar">
    <div>
        <h1>🧪 QuickSale API Tester <span>/ delete-users.php</span></h1>
        <div class="subtitle">Login first → then delete a user by ID</div>
    </div>
    <div class="nav-links">
        <a href="api-tester.html"    class="nav-link">➕ Add User</a>
        <a href="update-tester.html" class="nav-link">✏️ Update User</a>
        <a href="delete-tester.html" class="nav-link active">🗑️ Delete User</a>
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

        <!-- STEP 2: Delete -->
        <div class="panel">
            <div class="panel-header">
                <div class="step-badge sb-red">2</div>
                <h2>Delete User</h2>
            </div>
            <div class="panel-body">

                <div class="danger-box">
                    <strong>⚠️ Danger Zone</strong>
                    Deleting a user is permanent. If the user has linked sales records,
                    the API will <b>deactivate</b> them instead to preserve data integrity.
                </div>

                <div class="form-group">
                    <label>API URL</label>
                    <input type="text" id="deleteUrl" value="http://localhost/POS-System/backend/api/users/delete-users.php">
                </div>
                <div class="form-group">
                    <label>User ID to Delete <span style="color:#f87171">*</span></label>
                    <input type="number" id="d_id" value="4" min="1" oninput="updatePreview()">
                    <div class="hint">⚠️ Do NOT enter ID 1 (admin) — the API will block it anyway</div>
                </div>

                <div class="divider">— JSON Payload Preview —</div>
                <div class="payload-preview" id="payloadPreview"></div>

                <button class="btn btn-red" id="deleteBtn" onclick="askConfirm()">🗑️ Delete User</button>
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

        <!-- Delete Response -->
        <div class="panel">
            <div class="panel-header">
                <div class="step-badge sb-red">2</div>
                <h2>Delete Response</h2>
            </div>
            <div class="panel-body">
                <div id="deleteStatus" class="status-bar idle">
                    <div class="dot idle" id="deleteDot"></div>
                    <span id="deleteStatusText">Waiting for Step 1...</span>
                </div>
                <div id="deleteHttpMeta" class="http-meta"></div>
                <div id="deleteResponse" class="response-box">// Delete response will appear here</div>
            </div>
        </div>

        <!-- Safety Rules Reference -->
        <div class="panel">
            <div class="panel-header">
                <div class="step-badge" style="background:#475569;color:#e2e8f0;">?</div>
                <h2>API Safety Rules</h2>
            </div>
            <div class="panel-body">
                <div class="rules-box">
                    <strong>The delete API enforces these rules:</strong><br><br>
                    <div class="rule-item">
                        <span class="rule-icon">🔴</span>
                        <span class="rule-text"><b>401</b> — Not logged in</span>
                    </div>
                    <div class="rule-item">
                        <span class="rule-icon">🔴</span>
                        <span class="rule-text"><b>403</b> — Non-admin trying to delete</span>
                    </div>
                    <div class="rule-item">
                        <span class="rule-icon">🔴</span>
                        <span class="rule-text"><b>403</b> — Trying to delete your own account</span>
                    </div>
                    <div class="rule-item">
                        <span class="rule-icon">🔴</span>
                        <span class="rule-text"><b>403</b> — Deleting the last active admin</span>
                    </div>
                    <div class="rule-item">
                        <span class="rule-icon">🔴</span>
                        <span class="rule-text"><b>404</b> — User ID doesn't exist</span>
                    </div>
                    <div class="rule-item">
                        <span class="rule-icon">🟡</span>
                        <span class="rule-text"><b>200</b> — Has sales records → <b>deactivated</b> instead</span>
                    </div>
                    <div class="rule-item">
                        <span class="rule-icon">🟢</span>
                        <span class="rule-text"><b>200</b> — Successfully deleted</span>
                    </div>
                    <br>
                    <strong>Sample users in DB:</strong><br>
                    ID 1 → admin &nbsp;|&nbsp; ID 2 → cashier1 &nbsp;|&nbsp; ID 3 → manager1
                </div>
            </div>
        </div>

    </div><!-- /right -->

</div><!-- /layout -->

<script>
let isLoggedIn = false;
let pendingDeleteId = null;

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
    document.getElementById(prefix + 'Status').className       = 'status-bar ' + state;
    document.getElementById(prefix + 'Dot').className          = 'dot ' + state;
    document.getElementById(prefix + 'StatusText').textContent = text;
}

// ── HTTP chips ──
function setHttpMeta(prefix, method, status, ms) {
    document.getElementById(prefix + 'HttpMeta').innerHTML =
        `<span class="http-chip chip-${method}">${method}</span>
         <span class="http-chip chip-${status}">HTTP ${status}</span>
         <span class="http-chip chip-time">⏱ ${ms}ms</span>`;
}

// ── Payload preview ──
function updatePreview() {
    const id = parseInt(document.getElementById('d_id').value) || 0;
    const payload = { id };
    document.getElementById('payloadPreview').innerHTML =
        syntaxHighlight(JSON.stringify(payload, null, 2));
}

// ── Confirm overlay ──
function askConfirm() {
    if (!isLoggedIn) {
        setStatus('delete', 'error', '❌ Login first (Step 1).');
        return;
    }
    const id = parseInt(document.getElementById('d_id').value) || 0;
    if (!id) {
        setStatus('delete', 'error', '❌ Enter a valid User ID.');
        return;
    }
    pendingDeleteId = id;
    document.getElementById('confirmIdDisplay').textContent = 'User ID: ' + id;
    document.getElementById('confirmOverlay').classList.add('visible');
}

function cancelDelete() {
    pendingDeleteId = null;
    document.getElementById('confirmOverlay').classList.remove('visible');
}

function confirmDelete() {
    document.getElementById('confirmOverlay').classList.remove('visible');
    doDelete(pendingDeleteId);
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
            setStatus('delete', 'idle', 'Ready — enter a User ID and click Delete');
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

// ── STEP 2: Delete ──
async function doDelete(id) {
    const url = document.getElementById('deleteUrl').value.trim();
    const payload = { id };

    setStatus('delete', 'loading', 'Sending delete request...');
    document.getElementById('deleteBtn').disabled = true;
    document.getElementById('deleteHttpMeta').innerHTML = '';
    document.getElementById('deleteResponse').textContent = '// Loading...';

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

        setHttpMeta('delete', 'POST', res.status, ms);

        let parsed;
        try { parsed = JSON.parse(text); } catch { parsed = null; }

        document.getElementById('deleteResponse').innerHTML =
            parsed ? syntaxHighlight(JSON.stringify(parsed, null, 2)) : text;

        if (res.ok && parsed?.success) {
            // Check if it was deleted or deactivated
            const msg = parsed.message ?? '';
            const wasDeactivated = msg.toLowerCase().includes('deactivat');
            setStatus('delete', 'success',
                wasDeactivated
                    ? '🟡 User deactivated (had sales records)'
                    : '✅ User deleted successfully!'
            );
        } else {
            setStatus('delete', 'error', '❌ ' + (parsed?.message ?? parsed?.error ?? 'Request failed.'));
        }
    } catch (err) {
        setStatus('delete', 'error', '❌ Network error: ' + err.message);
        document.getElementById('deleteResponse').textContent = 'Error: ' + err.message;
    } finally {
        document.getElementById('deleteBtn').disabled = false;
    }
}

// Init
updatePreview();
</script>
</body>
</html>