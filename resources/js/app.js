import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

/* =========================
   HELPER FORMAT TANGGAL
========================= */
window.formatTanggal = function (datetime, options = {}) {
    if (!datetime) return '-';

    const date = new Date(datetime);

    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        timeZone: 'Asia/Makassar',
        ...options
    });
};

function closeActionMenus(exceptMenu = null) {
    document.querySelectorAll('.action-menu.is-open').forEach((menu) => {
        if (menu === exceptMenu) {
            return;
        }

        menu.classList.remove('is-open');
        const button = menu.querySelector('[data-action-menu-trigger]');
        if (button) {
            button.setAttribute('aria-expanded', 'false');
        }
    });
}

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-action-menu-trigger]');
    const menu = event.target.closest('.action-menu');
    const insidePanel = event.target.closest('.action-menu__panel');

    if (trigger && menu) {
        event.preventDefault();
        const shouldOpen = !menu.classList.contains('is-open');
        closeActionMenus(menu);
        menu.classList.toggle('is-open', shouldOpen);
        trigger.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        return;
    }

    if (insidePanel) {
        return;
    }

    closeActionMenus();
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    closeActionMenus();
});

Alpine.start();
