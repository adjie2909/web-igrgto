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

function showFlashModal(type, message) {
    const normalizedType = type === 'error' ? 'error' : 'success';
    const title = normalizedType === 'error' ? 'Peringatan' : 'Berhasil';
    const existing = document.getElementById('codexFlashModal');
    if (existing) {
        existing.remove();
    }

    const modal = document.createElement('div');
    modal.id = 'codexFlashModal';
    modal.className = 'modal modal-success show';

    const iconClass = normalizedType === 'error' ? 'error-icon' : 'success-icon';
    modal.innerHTML = `
        <div class="modal-content modal-content--success success-content">
            <div class="${iconClass}" aria-hidden="true">${normalizedType === 'error' ? '!' : ''}</div>
            <h3 class="success-title">${title}</h3>
            <p class="success-message">${String(message ?? '')}</p>
            <button class="btn btn-primary" type="button" data-codex-flash-close>OK</button>
        </div>
    `;

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.remove();
        }
    });

    modal.querySelector('[data-codex-flash-close]')?.addEventListener('click', () => {
        modal.remove();
    });

    document.body.appendChild(modal);

    window.setTimeout(() => {
        modal.remove();
    }, normalizedType === 'error' ? 4500 : 3000);
}

window.setFlashAndRedirect = function (type, message, url) {
    const key = type === 'error' ? 'flash_error' : 'flash_success';
    try {
        sessionStorage.setItem(key, String(message ?? ''));
    } catch (e) {
        // ignore
    }

    if (url) {
        window.location.href = url;
    } else {
        window.location.reload();
    }
};

function updateActionMenuDirection(menu) {
    if (!menu) return;

    const panel = menu.querySelector('.action-menu__panel');
    const trigger = menu.querySelector('.action-menu__trigger') || menu.querySelector('[data-action-menu-trigger]');

    if (!panel || !trigger) return;

    menu.classList.remove('action-menu--up');

    const panelHeight = panel.offsetHeight || 0;
    if (panelHeight <= 0) return;

    const margin = 12;
    const triggerRect = trigger.getBoundingClientRect();
    const boundsCandidates = [window.innerHeight];
    const clippingContainer = menu.closest('.table-wrap, .table-section, .card, .modal-content');

    if (clippingContainer) {
        const containerRect = clippingContainer.getBoundingClientRect();
        boundsCandidates.push(containerRect.bottom);
    }

    const lowerBound = Math.min(...boundsCandidates);

    const wouldOverflowBottom = triggerRect.bottom + margin + panelHeight > lowerBound;
    const wouldOverflowTop = triggerRect.top - margin - panelHeight < 0;

    if (wouldOverflowBottom && !wouldOverflowTop) {
        menu.classList.add('action-menu--up');
    }
}

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

document.addEventListener(
    'toggle',
    (event) => {
        const menu = event.target;
        if (!(menu instanceof HTMLElement)) return;
        if (!menu.matches('details.action-menu')) return;
        const row = menu.closest('tr');
        if (!menu.hasAttribute('open')) {
            menu.classList.remove('action-menu--up');
            if (row) {
                const anyOpen = !!row.querySelector('details.action-menu[open]');
                row.classList.toggle('has-action-open', anyOpen);
            }
            return;
        }

        updateActionMenuDirection(menu);
        if (row) {
            row.classList.add('has-action-open');
        }
    },
    true
);

window.addEventListener('resize', () => {
    document.querySelectorAll('details.action-menu[open]').forEach((menu) => updateActionMenuDirection(menu));
});

document.addEventListener('DOMContentLoaded', () => {
    try {
        const successMessage = sessionStorage.getItem('flash_success');
        const errorMessage = sessionStorage.getItem('flash_error');

        if (successMessage) {
            sessionStorage.removeItem('flash_success');
            showFlashModal('success', successMessage);
        } else if (errorMessage) {
            sessionStorage.removeItem('flash_error');
            showFlashModal('error', errorMessage);
        }
    } catch (e) {
        // ignore
    }
});

Alpine.start();
