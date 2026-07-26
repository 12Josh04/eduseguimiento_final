document.addEventListener('DOMContentLoaded', function () {

    // Sidebar toggle
    const toggle  = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const main    = document.getElementById('mainContent');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            if (main) main.classList.toggle('expanded');
        });
    }

    // User dropdown
    const userMenu     = document.getElementById('userMenuToggle');
    const userDropdown = document.getElementById('userDropdown');
    if (userMenu && userDropdown) {
        userMenu.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });
        document.addEventListener('click', function () {
            userDropdown.classList.remove('open');
        });
    }

    // Alerts auto-close
    document.querySelectorAll('.alert-close').forEach(btn => {
        btn.addEventListener('click', function () { this.closest('.alert').remove(); });
    });
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            alert.style.transition = '.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 4500);
    });

    // Modals — abrir
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', function () {
            const m = document.getElementById(this.dataset.modalOpen);
            if (m) m.classList.add('open');
        });
    });

    // Modals — cerrar (botón .modal-close o clic en overlay)
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) this.classList.remove('open');
        });
    });
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function () {
            this.closest('.modal-overlay').classList.remove('open');
        });
    });

    // Búsqueda en tabla
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const val = this.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
            });
        });
    }

    // Colores de notas
    document.querySelectorAll('.nota-val').forEach(cell => {
        const val = parseFloat(cell.textContent);
        if (!isNaN(val)) {
            cell.classList.add(val >= 14 ? 'nota-alta' : val >= 11 ? 'nota-media' : 'nota-baja');
        }
    });

    // Confirmación de borrado
    document.querySelectorAll('[data-confirm]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm || '¿Estás seguro?')) e.preventDefault();
        });
    });

    // Progress bars animadas
    document.querySelectorAll('.progress-bar-fill').forEach(bar => {
        const w = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => { bar.style.width = w; }, 200);
    });

    // Gráfica de barras simple (canvas)
    document.querySelectorAll('.bar-chart-simple').forEach(canvas => {
        // Los promedios llegan de MySQL vía PDO, que a menudo los serializa como texto
        // (ej. "14.5" en lugar de 14.5); se normalizan a número para evitar errores como
        // val.toFixed is not a function, que interrumpían el dibujo de las demás barras.
        const data   = JSON.parse(canvas.dataset.values || '[]').map(Number);
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        if (!data.length) return;
        const ctx = canvas.getContext('2d');
        canvas.width  = canvas.offsetWidth || 600;
        canvas.height = canvas.offsetHeight || 200;
        const w = canvas.width, h = canvas.height;
        const max = Math.max(...data, 20);
        const pad = { top: 24, right: 10, bottom: 32, left: 38 };
        const barW = (w - pad.left - pad.right) / data.length - 8;

        ctx.clearRect(0, 0, w, h);
        [0, 5, 10, 15, 20].forEach(v => {
            const y = pad.top + (h - pad.top - pad.bottom) * (1 - v / max);
            ctx.strokeStyle = '#e2e6ee'; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(w - pad.right, y); ctx.stroke();
            ctx.fillStyle = '#9aa3b2'; ctx.font = '10px Inter';
            ctx.textAlign = 'right'; ctx.fillText(v, pad.left - 4, y + 3);
        });

        data.forEach((val, i) => {
            const x  = pad.left + i * ((w - pad.left - pad.right) / data.length) + 4;
            const bh = (val / max) * (h - pad.top - pad.bottom);
            const y  = h - pad.bottom - bh;
            ctx.fillStyle = val >= 14 ? '#0ea27a' : val >= 11 ? '#e07b2a' : '#c0392b';
            ctx.beginPath();
            if (ctx.roundRect) ctx.roundRect(x, y, barW, bh, [4, 4, 0, 0]);
            else ctx.rect(x, y, barW, bh);
            ctx.fill();
            ctx.fillStyle = '#1e2532'; ctx.font = 'bold 10px Inter'; ctx.textAlign = 'center';
            ctx.fillText(val.toFixed(1), x + barW / 2, y - 5);
            ctx.fillStyle = '#9aa3b2'; ctx.font = '10px Inter';
            ctx.fillText(labels[i] || '', x + barW / 2, h - 6);
        });
    });
});
