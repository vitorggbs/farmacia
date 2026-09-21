(function () {
    'use strict';

    const root = window.FARMA_AI_ROOT || '';
    const launcher = document.getElementById('farmaAiLauncher');
    const panel = document.getElementById('farmaAiPanel');
    const closeBtn = document.getElementById('farmaAiClose');
    const form = document.getElementById('farmaAiForm');
    const input = document.getElementById('farmaAiInput');
    const messages = document.getElementById('farmaAiMessages');
    const sendBtn = document.getElementById('farmaAiSend');
    const chips = document.querySelectorAll('.farma-ai-chip');

    if (!launcher || !panel || !form || !input || !messages) return;

    const history = [];
    let busy = false;

    function openPanel() {
        panel.classList.add('is-open');
        panel.setAttribute('aria-hidden', 'false');
        launcher.setAttribute('aria-expanded', 'true');
        setTimeout(function () { input.focus(); }, 100);
    }

    function closePanel() {
        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        launcher.setAttribute('aria-expanded', 'false');
        launcher.focus();
    }

    function addMessage(role, text, extraClass) {
        const row = document.createElement('div');
        row.className = 'farma-ai-message ' + role + (extraClass ? ' ' + extraClass : '');
        const bubble = document.createElement('div');
        bubble.className = 'farma-ai-bubble';
        bubble.textContent = text;
        row.appendChild(bubble);
        messages.appendChild(row);
        messages.scrollTop = messages.scrollHeight;
        return row;
    }

    async function sendMessage(text) {
        text = (text || '').trim();
        if (!text || busy) return;

        busy = true;
        sendBtn.disabled = true;
        input.disabled = true;
        addMessage('user', text);
        history.push({ role: 'user', text: text });
        if (history.length > 12) history.splice(0, history.length - 12);

        input.value = '';
        input.style.height = '42px';
        const typing = addMessage('assistant', 'Pensando…', 'farma-ai-typing');

        try {
            const response = await fetch(root + 'api/agente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') || {}).content || ''
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    mensagem: text,
                    historico: history.slice(0, -1),
                    pagina: window.location.pathname
                })
            });

            let data = {};
            try { data = await response.json(); } catch (e) {}
            typing.remove();

            if (!response.ok || !data.ok) {
                addMessage('assistant', data.erro || 'Não consegui falar com o agente agora. Tente novamente em alguns segundos.');
                return;
            }

            const answer = data.resposta || 'Não recebi uma resposta do agente.';
            addMessage('assistant', answer);
            history.push({ role: 'assistant', text: answer });
            if (history.length > 12) history.splice(0, history.length - 12);
        } catch (error) {
            typing.remove();
            addMessage('assistant', 'O agente está indisponível no momento. Verifique a conexão e tente novamente.');
        } finally {
            busy = false;
            sendBtn.disabled = false;
            input.disabled = false;
            input.focus();
        }
    }

    launcher.addEventListener('click', function () {
        if (panel.classList.contains('is-open')) closePanel(); else openPanel();
    });
    closeBtn.addEventListener('click', closePanel);

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        sendMessage(input.value);
    });

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    input.addEventListener('input', function () {
        input.style.height = '42px';
        input.style.height = Math.min(input.scrollHeight, 110) + 'px';
    });

    chips.forEach(function (chip) {
        chip.addEventListener('click', function () {
            openPanel();
            sendMessage(chip.getAttribute('data-prompt') || chip.textContent);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && panel.classList.contains('is-open')) closePanel();
    });
})();
