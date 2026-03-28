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

Alpine.start();
