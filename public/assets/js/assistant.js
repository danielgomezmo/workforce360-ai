(() => {
    const root = document.getElementById('wfAssistant');
    if (!root) return;

    const toggle = root.querySelector('[data-wf-assistant-toggle]');
    const panel = root.querySelector('[data-wf-assistant-panel]');
    const close = root.querySelector('[data-wf-assistant-close]');
    const form = root.querySelector('[data-wf-assistant-form]');
    const input = root.querySelector('[data-wf-assistant-input]');
    const messages = root.querySelector('[data-wf-assistant-messages]');
    const clear = root.querySelector('[data-wf-assistant-clear]');
    const chips = root.querySelectorAll('[data-wf-assistant-chip]');
    const endpoint = root.dataset.endpoint;
    const token = root.dataset.token;
    const mode = root.dataset.mode || 'PERSONAL';
    let busy = false;

    const openPanel = () => {
        panel.classList.add('is-open');
        panel.setAttribute('aria-hidden', 'false');
        toggle.setAttribute('aria-expanded', 'true');
        window.setTimeout(() => input.focus(), 100);
    };

    const closePanel = () => {
        panel.classList.remove('is-open');
        panel.setAttribute('aria-hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
    };

    const addMessage = (text, who = 'assistant', extraClass = '') => {
        const row = document.createElement('div');
        row.className = `wf-assistant-message-row ${who} ${extraClass}`.trim();

        const bubble = document.createElement('div');
        bubble.className = 'wf-assistant-message';
        bubble.textContent = text;

        row.appendChild(bubble);
        messages.appendChild(row);
        messages.scrollTop = messages.scrollHeight;
        return row;
    };

    const addTyping = () => {
        const row = document.createElement('div');
        row.className = 'wf-assistant-message-row assistant wf-assistant-typing-row';
        row.innerHTML = '<div class="wf-assistant-message wf-assistant-typing"><span></span><span></span><span></span></div>';
        messages.appendChild(row);
        messages.scrollTop = messages.scrollHeight;
        return row;
    };

    const send = async (question) => {
        const message = (question || input.value || '').trim();
        if (!message || busy) return;

        busy = true;
        input.value = '';
        input.disabled = true;
        form.querySelector('button[type="submit"]').disabled = true;
        addMessage(message, 'user');
        const typing = addTyping();

        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 38000);

        try {
            const body = new URLSearchParams();
            body.set('_token', token);
            body.set('message', message);

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body.toString(),
                signal: controller.signal
            });

            const data = await response.json().catch(() => ({
                ok: false,
                message: 'El servidor devolvió una respuesta no válida.'
            }));

            typing.remove();
            addMessage(data.message || 'No pude procesar la consulta.', 'assistant', data.ok ? '' : 'error');
        } catch (error) {
            typing.remove();
            const timeoutMessage = error && error.name === 'AbortError'
                ? 'La consulta tardó demasiado. Si preguntaste por una predicción semanal, vuelve a intentarlo; ese cálculo puede demorar algunos segundos.'
                : 'No pude comunicarme con el asistente. Verifica tu conexión y vuelve a intentar.';
            addMessage(timeoutMessage, 'assistant', 'error');
        } finally {
            window.clearTimeout(timeout);
            busy = false;
            input.disabled = false;
            form.querySelector('button[type="submit"]').disabled = false;
            input.focus();
        }
    };

    toggle.addEventListener('click', () => {
        panel.classList.contains('is-open') ? closePanel() : openPanel();
    });
    close.addEventListener('click', closePanel);

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        send();
    });

    chips.forEach((chip) => {
        chip.addEventListener('click', () => send(chip.dataset.wfAssistantChip || chip.textContent));
    });

    clear.addEventListener('click', () => {
        messages.innerHTML = '';
        const greeting = mode === 'ADMIN'
            ? 'Hola. Puedo consultar datos globales de Workforce360 AI y las predicciones del modelo. ¿Qué deseas revisar?'
            : 'Hola. Puedo consultar únicamente tus datos personales de asistencia, tardanzas, faltas e incidencias. ¿Qué deseas revisar?';
        addMessage(greeting, 'assistant');
    });
})();
