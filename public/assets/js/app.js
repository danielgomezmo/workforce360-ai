(() => {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('toggleSidebar');
    const storageKey = 'workforce360SidebarCollapsed';

    if (sidebar && toggle) {
        if (window.localStorage.getItem(storageKey) === '1') {
            sidebar.classList.add('collapsed');
        }

        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            window.localStorage.setItem(storageKey, sidebar.classList.contains('collapsed') ? '1' : '0');
        });
    }

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.dataset.confirm || '¿Deseas continuar?';
            if (!window.confirm(message)) event.preventDefault();
        });
    });

    const clock = document.getElementById('serverClock');
    if (clock && clock.dataset.serverTime) {
        let now = new Date(clock.dataset.serverTime);
        const formatter = new Intl.DateTimeFormat('es-PE', {
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true, timeZone: 'America/Lima'
        });
        const render = () => {
            clock.textContent = formatter.format(now);
            now = new Date(now.getTime() + 1000);
        };
        render();
        window.setInterval(render, 1000);
    }
})();
