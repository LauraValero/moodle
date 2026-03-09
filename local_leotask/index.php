<?php
/**
 * Middleware test page for lv-llm actions.
 *
 * Accessible at: http://localhost:8080/local/leotask/
 * Requires: teacher/admin login in Moodle.
 */
require_once(__DIR__ . '/../../config.php');
require_login();

$courseid = optional_param('courseid', 0, PARAM_INT);
$sectionid = optional_param('sectionid', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/leotask/index.php'));
$PAGE->set_title('Moodle — Middleware Test');
$PAGE->set_heading('Moodle — Prueba de Actions');

echo $OUTPUT->header();
?>

<style>
    .leo-container { max-width: 900px; margin: 0 auto; }

    /* Action cards */
    .leo-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .leo-action-card {
        flex: 1; min-width: 180px; padding: 14px; border: 2px solid #dee2e6; border-radius: 8px;
        cursor: pointer; transition: all 0.2s; background: #fff; text-align: center;
    }
    .leo-action-card:hover { border-color: #0f6cbf; background: #f0f7ff; }
    .leo-action-card.selected { border-color: #0f6cbf; background: #e7f1ff; box-shadow: 0 0 0 3px rgba(15,108,191,0.15); }
    .leo-action-card .ico { font-size: 22px; }
    .leo-action-card h5 { margin: 6px 0 2px; font-size: 14px; }
    .leo-action-card p { margin: 0; font-size: 11px; color: #6c757d; }

    /* Chat area */
    .leo-chat-box {
        background: #fff; border: 1px solid #dee2e6; border-radius: 8px;
        margin-top: 16px; display: flex; flex-direction: column; height: 500px;
    }
    .leo-messages { flex: 1; overflow-y: auto; padding: 16px; }
    .leo-msg { margin-bottom: 12px; max-width: 85%; }
    .leo-msg.user { margin-left: auto; }
    .leo-msg.user .leo-bubble { background: #0f6cbf; color: #fff; border-radius: 12px 12px 0 12px; padding: 10px 14px; }
    .leo-msg.assistant .leo-bubble { background: #f0f0f0; color: #1e1e1e; border-radius: 12px 12px 12px 0; padding: 10px 14px; }
    .leo-msg .leo-bubble { font-size: 14px; line-height: 1.6; word-wrap: break-word; }
    .leo-msg.user .leo-bubble { white-space: pre-wrap; }

    /* Markdown inside assistant bubbles */
    .leo-msg.assistant .leo-bubble p { margin: 0 0 8px; }
    .leo-msg.assistant .leo-bubble p:last-child { margin-bottom: 0; }
    .leo-msg.assistant .leo-bubble ul,
    .leo-msg.assistant .leo-bubble ol { margin: 4px 0 8px; padding-left: 20px; }
    .leo-msg.assistant .leo-bubble li { margin-bottom: 2px; }
    .leo-msg.assistant .leo-bubble strong { font-weight: 700; }
    .leo-msg.assistant .leo-bubble em { font-style: italic; }
    .leo-msg.assistant .leo-bubble code {
        background: rgba(0,0,0,0.06); padding: 1px 5px; border-radius: 3px;
        font-family: 'Courier New', monospace; font-size: 13px;
    }
    .leo-msg.assistant .leo-bubble pre {
        background: #1e1e1e; color: #d4d4d4; padding: 10px 12px; border-radius: 6px;
        overflow-x: auto; margin: 8px 0; font-size: 12px;
    }
    .leo-msg.assistant .leo-bubble pre code {
        background: none; padding: 0; color: inherit; font-size: inherit;
    }
    .leo-msg.assistant .leo-bubble h1,
    .leo-msg.assistant .leo-bubble h2,
    .leo-msg.assistant .leo-bubble h3 {
        margin: 12px 0 6px; font-size: 15px; font-weight: 700;
    }
    .leo-msg.assistant .leo-bubble h1 { font-size: 17px; }
    .leo-msg.assistant .leo-bubble h2 { font-size: 16px; }
    .leo-msg.assistant .leo-bubble blockquote {
        border-left: 3px solid #ccc; margin: 8px 0; padding: 4px 12px; color: #555;
    }
    .leo-msg.assistant .leo-bubble table {
        border-collapse: collapse; margin: 8px 0; width: 100%; font-size: 13px;
    }
    .leo-msg.assistant .leo-bubble th,
    .leo-msg.assistant .leo-bubble td {
        border: 1px solid #ddd; padding: 6px 10px; text-align: left;
    }
    .leo-msg.assistant .leo-bubble th { background: #f5f5f5; font-weight: 600; }
    .leo-msg .leo-label { font-size: 11px; color: #999; margin-bottom: 2px; }
    .leo-msg.user .leo-label { text-align: right; }
    .leo-typing { color: #999; font-style: italic; font-size: 13px; padding: 4px 0; }

    /* Input area */
    .leo-input-area {
        display: flex; gap: 8px; padding: 12px 16px;
        border-top: 1px solid #dee2e6; background: #fafafa; border-radius: 0 0 8px 8px;
    }
    .leo-input-area textarea {
        flex: 1; padding: 10px; border: 1px solid #ced4da; border-radius: 6px;
        font-size: 14px; resize: none; min-height: 44px; max-height: 120px;
        font-family: inherit;
    }
    .leo-input-area button {
        background: #0f6cbf; color: #fff; border: none; padding: 0 20px;
        border-radius: 6px; cursor: pointer; font-size: 14px; white-space: nowrap;
    }
    .leo-input-area button:hover { background: #0a5a9e; }
    .leo-input-area button:disabled { background: #6c757d; cursor: not-allowed; }

    /* Status & extras */
    .leo-toolbar { display: flex; justify-content: space-between; align-items: center; margin-top: 12px; }
    .leo-session-info { font-size: 11px; color: #999; }
    .leo-btn-sm { background: none; border: 1px solid #ced4da; padding: 4px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; color: #666; }
    .leo-btn-sm:hover { background: #f0f0f0; }
    .leo-status { padding: 6px 12px; border-radius: 4px; margin-top: 8px; display: none; font-size: 13px; }
    .leo-status.info { background: #cfe2ff; color: #084298; display: block; }
    .leo-status.ok { background: #d1e7dd; color: #0f5132; display: block; }
    .leo-status.err { background: #f8d7da; color: #842029; display: block; }
    .leo-json { background: #f0f7ff; border: 1px solid #b6d4fe; border-radius: 8px; padding: 16px; margin-top: 12px; display: none; }
    .leo-json pre { margin: 0; white-space: pre-wrap; font-size: 12px; }

    details { margin-bottom: 16px; }
    details summary { cursor: pointer; font-weight: 600; font-size: 13px; color: #666; }
    details[open] { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 12px; }
    details label { display: block; font-weight: 600; margin: 8px 0 4px; font-size: 13px; }
    details input { width: 100%; padding: 6px 10px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; }
</style>

<div class="leo-container">
    <details>
        <summary>⚙️ Configuración de conexión</summary>
        <label>Endpoint lv-llm</label>
        <input type="text" id="leo_endpoint" value="http://localhost:8000">
        <label>Bearer Token</label>
        <input type="text" id="leo_token" value="leo-middleware-dev-token-2026">
        <label>Course ID</label>
        <input type="number" id="leo_courseid" value="<?php echo $courseid ?: 2; ?>">
        <label>Section ID</label>
        <input type="number" id="leo_sectionid" value="<?php echo $sectionid ?: 1; ?>">
        <label>User ID (profesor)</label>
        <input type="number" id="leo_userid" value="<?php echo $USER->id; ?>">
        <label>Moodle URL (como la ve lv-llm)</label>
        <input type="text" id="leo_moodleurl" value="http://host.docker.internal:8080">
    </details>

    <div class="leo-actions">
        <div class="leo-action-card selected" onclick="selectAction(this,'create_assignment')">
            <span class="ico">📝</span><h5>Crear Tarea</h5>
            <p>Genera JSON de actividad assign</p>
        </div>
        <div class="leo-action-card" onclick="selectAction(this,'generate_text')">
            <span class="ico">✍️</span><h5>Generar Texto</h5>
            <p>Contenido, rúbricas, instrucciones</p>
        </div>
        <div class="leo-action-card" onclick="selectAction(this,'summarise_text')">
            <span class="ico">📄</span><h5>Resumir Texto</h5>
            <p>Condensa contenido</p>
        </div>
    </div>

    <div class="leo-chat-box">
        <div class="leo-messages" id="leo_messages"></div>
        <div class="leo-input-area">
            <textarea id="leo_input" placeholder="Escribe tu mensaje..." rows="1"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMessage()}"></textarea>
            <button id="leo_send" onclick="sendMessage()">Enviar</button>
        </div>
    </div>

    <div class="leo-toolbar">
        <span class="leo-session-info" id="leo_session_info"></span>
        <button class="leo-btn-sm" onclick="resetSession()">🔄 Nueva sesión</button>
    </div>

    <div class="leo-status" id="leo_status"></div>
    <div class="leo-json" id="leo_json"><h5>📦 JSON de actividad</h5><pre id="leo_json_content"></pre></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked@14/marked.min.js"></script>
<script>
marked.setOptions({ breaks: true, gfm: true });

let currentSessionId = null;
let currentAction = 'create_assignment';
let sending = false;

function selectAction(el, action) {
    document.querySelectorAll('.leo-action-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    currentAction = action;
    resetSession();
}

function resetSession() {
    currentSessionId = null;
    document.getElementById('leo_messages').innerHTML = '';
    document.getElementById('leo_session_info').textContent = '';
    document.getElementById('leo_json').style.display = 'none';
    document.getElementById('leo_status').style.display = 'none';
}

function setStatus(msg, type) {
    const el = document.getElementById('leo_status');
    el.className = 'leo-status ' + type;
    el.textContent = msg;
    el.style.display = 'block';
}

function addMessage(role, text) {
    const container = document.getElementById('leo_messages');
    const div = document.createElement('div');
    div.className = 'leo-msg ' + role;
    const label = role === 'user' ? 'Tú' : 'Asistente';
    div.innerHTML = '<div class="leo-label">' + label + '</div><div class="leo-bubble"></div>';
    const bubble = div.querySelector('.leo-bubble');
    if (role === 'user') {
        bubble.textContent = text;
    } else {
        bubble.innerHTML = text ? marked.parse(text) : '';
    }
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    return bubble;
}

function renderMarkdown(bubble, text) {
    bubble.innerHTML = marked.parse(text);
}

async function sendMessage() {
    if (sending) return;
    const input = document.getElementById('leo_input');
    const text = input.value.trim();
    if (!text) return;

    input.value = '';
    sending = true;
    document.getElementById('leo_send').disabled = true;
    document.getElementById('leo_json').style.display = 'none';

    // Show user message
    addMessage('user', text);

    // Build request body
    const endpoint = document.getElementById('leo_endpoint').value.trim();
    const token = document.getElementById('leo_token').value.trim();

    let body;
    if (currentSessionId) {
        body = {
            model: 'lv-llm',
            stream: true,
            session_id: currentSessionId,
            messages: [{ role: 'user', content: text }]
        };
    } else {
        body = {
            model: 'lv-llm',
            stream: true,
            action: currentAction,
            tenant_id: 'uniminuto',
            bot_name: 'leo',
            moodle_url: document.getElementById('leo_moodleurl').value.trim(),
            email: 'profesor@local.dev',
            course_id: parseInt(document.getElementById('leo_courseid').value) || 2,
            section_id: parseInt(document.getElementById('leo_sectionid').value) || 1,
            user_id: parseInt(document.getElementById('leo_userid').value) || 0,
            role: 'teacher',
            messages: [{ role: 'user', content: text }]
        };
    }

    // Create assistant bubble for streaming
    const bubble = addMessage('assistant', '');
    setStatus('Conectando...', 'info');

    try {
        const resp = await fetch(endpoint + '/v1/chat/completions', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify(body)
        });

        if (!resp.ok) {
            const err = await resp.text();
            bubble.innerHTML = '<em>Error: ' + err.replace(/</g,'&lt;') + '</em>';
            setStatus('Error ' + resp.status, 'err');
            sending = false;
            document.getElementById('leo_send').disabled = false;
            return;
        }

        setStatus('Recibiendo...', 'info');

        const reader = resp.body.getReader();
        const decoder = new TextDecoder();
        let fullText = '';
        let buf = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buf += decoder.decode(value, { stream: true });
            const lines = buf.split('\n');
            buf = lines.pop();

            for (const line of lines) {
                if (!line.startsWith('data: ')) continue;
                const data = line.slice(6).trim();
                if (data === '[DONE]') continue;

                try {
                    const obj = JSON.parse(data);

                    if (obj.session_id && !currentSessionId) {
                        currentSessionId = obj.session_id;
                        document.getElementById('leo_session_info').textContent =
                            currentAction + '  •  ' + currentSessionId.slice(0, 8) + '...';
                    }

                    const delta = obj.choices?.[0]?.delta?.content;
                    if (delta) {
                        fullText += delta;
                        renderMarkdown(bubble, fullText);
                        document.getElementById('leo_messages').scrollTop =
                            document.getElementById('leo_messages').scrollHeight;
                    }
                } catch (e) {}
            }
        }

        // Extract JSON, show in panel, and strip from chat bubble
        if (currentAction === 'create_assignment') {
            const cleanedText = tryExtractJson(fullText);
            if (cleanedText !== null) {
                renderMarkdown(bubble, cleanedText);
            }
        }

        document.getElementById('leo_status').style.display = 'none';

    } catch (err) {
        bubble.innerHTML = '<em>Error de conexión: ' + err.message.replace(/</g,'&lt;') + '</em>';
        setStatus('Error: ' + err.message, 'err');
    }

    sending = false;
    document.getElementById('leo_send').disabled = false;
    document.getElementById('leo_input').focus();
}

function tryExtractJson(text) {
    const codeBlockMatch = text.match(/```json\s*([\s\S]*?)```/);
    const rawMatch = !codeBlockMatch ? text.match(/\{[\s\S]*"activity"[\s\S]*\}/) : null;
    const match = codeBlockMatch || rawMatch;
    if (match) {
        try {
            const jsonStr = match[1] || match[0];
            const parsed = JSON.parse(jsonStr);
            document.getElementById('leo_json_content').textContent = JSON.stringify(parsed, null, 2);
            document.getElementById('leo_json').style.display = 'block';
            // Return text with JSON block stripped out
            return text.replace(/```json\s*[\s\S]*?```/, '').trim();
        } catch (e) {}
    }
    return null;
}
</script>

<?php
echo $OUTPUT->footer();
