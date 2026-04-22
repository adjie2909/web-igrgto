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

function buildCustomValidityMessage(field) {
    const label = field?.dataset?.fieldLabel || field?.getAttribute('aria-label') || 'Input';
    const itemName = (field?.dataset?.itemName || '').trim();
    const unit = (field?.dataset?.unit || '').trim();
    const suffix = unit ? ` ${unit}` : '';
    const prefix = itemName ? `${itemName}: ` : '';

    const max = field?.getAttribute('max');
    const min = field?.getAttribute('min');

    if (field.validity.valueMissing) {
        return `${prefix}${label} wajib diisi.`;
    }

    if (field.validity.rangeOverflow && max !== null && max !== '') {
        return `${prefix}${label} maksimal ${max}${suffix}.`;
    }

    if (field.validity.rangeUnderflow && min !== null && min !== '') {
        return `${prefix}${label} minimal ${min}${suffix}.`;
    }

    if (field.validity.stepMismatch) {
        return `${prefix}${label} tidak sesuai kelipatan yang diizinkan.`;
    }

    if (field.validity.typeMismatch) {
        return `${prefix}${label} formatnya tidak valid.`;
    }

    if (field.validity.patternMismatch) {
        return `${prefix}${label} formatnya tidak sesuai.`;
    }

    return `${prefix}${label} tidak valid.`;
}

let lastCustomValidityShownAt = 0;
document.addEventListener(
    'invalid',
    (event) => {
        const field = event.target;
        if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
            return;
        }

        if (field.dataset.customValidity !== '1') {
            return;
        }

        // Cancel the native browser tooltip bubble.
        event.preventDefault();

        const now = Date.now();
        if (now - lastCustomValidityShownAt < 250) {
            return;
        }
        lastCustomValidityShownAt = now;

        try {
            field.scrollIntoView({ block: 'center', inline: 'nearest' });
        } catch (e) {
            // ignore
        }
        try {
            field.focus({ preventScroll: true });
        } catch (e) {
            // ignore
        }

        showFlashModal('error', buildCustomValidityMessage(field));
    },
    true
);

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
    const lowerBound = getActionMenuLowerBound(menu);

    const wouldOverflowBottom = triggerRect.bottom + margin + panelHeight > lowerBound;
    const wouldOverflowTop = triggerRect.top - margin - panelHeight < 0;

    if (wouldOverflowBottom && !wouldOverflowTop) {
        menu.classList.add('action-menu--up');
    }
}

function getActionMenuLowerBound(menu) {
    let lowerBound = window.innerHeight;
    let current = menu.parentElement;

    while (current) {
        const styles = window.getComputedStyle(current);
        const canClipY = ['hidden', 'auto', 'scroll', 'clip'].includes(styles.overflowY);

        if (canClipY) {
            const rect = current.getBoundingClientRect();
            lowerBound = Math.min(lowerBound, rect.bottom);
        }

        current = current.parentElement;
    }

    return lowerBound;
}

function closeActionMenus(exceptMenu = null) {
    document.querySelectorAll('.action-menu.is-open').forEach((menu) => {
        if (menu === exceptMenu) {
            return;
        }

        menu.classList.remove('is-open');
        syncActionRowSpacing(menu);
        resetDetailsActionMenuAnimation(menu);
        const button = menu.querySelector('[data-action-menu-trigger]');
        if (button) {
            button.setAttribute('aria-expanded', 'false');
        }
    });
}

function resetDetailsActionMenuAnimation(menu) {
    if (!menu) return;
    const panel = menu.querySelector('.action-menu__panel');
    if (!panel) return;

    panel.style.removeProperty('transition');
    panel.style.removeProperty('opacity');
    panel.style.removeProperty('transform');
    panel.style.removeProperty('pointer-events');
    panel.style.removeProperty('visibility');
}

function animateDetailsActionMenuOpen(menu) {
    if (!menu) return;
    const panel = menu.querySelector('.action-menu__panel');
    if (!panel) return;

    // Prime a known "closed" visual state, then transition to the open state.
    const isUp = menu.classList.contains('action-menu--up');
    const fromTransform = isUp ? 'translateY(10px) scale(0.98)' : 'translateY(-10px) scale(0.98)';

    panel.style.transition = 'none';
    panel.style.visibility = 'visible';
    panel.style.pointerEvents = 'none';
    panel.style.opacity = '0';
    panel.style.transform = fromTransform;
    // Force reflow so the browser commits the initial state.
    void panel.offsetHeight;

    panel.style.transition = 'opacity 0.26s ease, transform 0.28s cubic-bezier(0.22, 1, 0.36, 1)';
    window.requestAnimationFrame(() => {
        panel.style.opacity = '1';
        panel.style.transform = 'translateY(0) scale(1)';
        panel.style.pointerEvents = 'auto';
    });

    panel.addEventListener(
        'transitionend',
        () => {
            // Hand control back to CSS after the first open animation.
            resetDetailsActionMenuAnimation(menu);
        },
        { once: true }
    );
}

function syncActionRowSpacing(menu) {
    if (!menu) return;

    const row = menu.closest('tr');
    const panel = menu.querySelector('.action-menu__panel');

    if (!row || !panel) return;

    if (!menu.hasAttribute('open')) {
        resetDetailsActionMenuAnimation(menu);
        row.style.removeProperty('--action-open-space');
        row.classList.remove('has-action-open');
        return;
    }

    const isUp = menu.classList.contains('action-menu--up');
    const panelHeight = panel.offsetHeight || 0;
    const extraSpace = isUp ? 0 : Math.max(0, panelHeight + 18);

    row.style.setProperty('--action-open-space', `${extraSpace}px`);
    row.classList.add('has-action-open');
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
            resetDetailsActionMenuAnimation(menu);
            if (row) {
                const anyOpen = !!row.querySelector('details.action-menu[open]');
                row.classList.toggle('has-action-open', anyOpen);
                if (!anyOpen) {
                    row.style.removeProperty('--action-open-space');
                }
            }
            return;
        }

        updateActionMenuDirection(menu);
        animateDetailsActionMenuOpen(menu);
        syncActionRowSpacing(menu);
    },
    true
);

window.addEventListener('resize', () => {
    document.querySelectorAll('details.action-menu[open]').forEach((menu) => {
        updateActionMenuDirection(menu);
        syncActionRowSpacing(menu);
    });
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

    // Prevent accidental double-submit for normal forms (in addition to server-side de-dupe).
    document.querySelectorAll('form').forEach((form) => {
        if (!(form instanceof HTMLFormElement)) return;
        if (form.dataset.preventDoubleSubmitBound === '1') return;
        form.dataset.preventDoubleSubmitBound = '1';

        form.addEventListener('submit', (event) => {
            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = '1';

            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((el) => {
                try {
                    el.disabled = true;
                } catch (e) {
                    // ignore
                }
            });
        });
    });
});

Alpine.start();
