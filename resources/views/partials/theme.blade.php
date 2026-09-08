{{-- Motor de temas: oscuro (predeterminado) y claro. La preferencia se guarda en localStorage. --}}
<script>
    (function () {
        var saved = null;
        try { saved = localStorage.getItem('santini-theme'); } catch (e) {}
        var light = saved ? saved === 'light'
            : (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches);
        if (light) {
            document.documentElement.classList.add('light');
            var meta = document.querySelector('meta[name="theme-color"]');
            if (meta) meta.setAttribute('content', '#f4f0ea');
        }
    })();

    document.addEventListener('DOMContentLoaded', function () {
        var buttons = document.querySelectorAll('[data-theme-toggle]');
        for (var i = 0; i < buttons.length; i++) {
            buttons[i].addEventListener('click', function () {
                var html = document.documentElement;
                var light = html.classList.toggle('light');
                try { localStorage.setItem('santini-theme', light ? 'light' : 'dark'); } catch (e) {}
                var meta = document.querySelector('meta[name="theme-color"]');
                if (meta) meta.setAttribute('content', light ? '#f4f0ea' : '#4A90E2');
            });
        }
    });
</script>

<style>
    :root { color-scheme: dark; }
    html.light { color-scheme: light; }

    /* Superficie base: degradado cálido lumenes que sigue el eje azul → morado de la marca */
    html.light body {
        background-image: linear-gradient(135deg, #faf8f4 0%, #eef2fa 55%, #f4effc 100%);
        color: #0f172a;
    }
    html.light .bg-blue-500\/10 { background-color: rgba(96, 165, 250, .28); }
    html.light .bg-blue-600\/10 { background-color: rgba(59, 130, 246, .14); }

    /* Cristal (cards, header) */
    html.light .glass { background: rgba(255, 255, 255, .68); border-color: rgba(100, 116, 139, .28); }
    html.light .glass-card { background: rgba(255, 255, 255, .58); border-color: rgba(100, 116, 139, .22); }

    /* Inputs */
    html.light .input-glass { background: rgba(255, 255, 255, .85); border-color: rgba(100, 116, 139, .38); color: #1e293b; }
    html.light .input-glass::placeholder { color: #94a3b8; }
    html.light .bg-gray-900\/60, html.light .bg-gray-900\/50 { background-color: rgba(255, 255, 255, .85); }
    html.light .placeholder-white\/40::placeholder { color: #94a3b8; }

    /* Texto principal blanco → escala slate */
    html.light .text-white { color: #0f172a; }
    html.light .text-white\/90 { color: #1e293b; }
    html.light .text-white\/80 { color: #334155; }
    html.light .text-white\/70 { color: #475569; }
    html.light .text-white\/60, html.light .text-white\/50 { color: #64748b; }
    html.light .text-white\/40 { color: #94a3b8; }
    html.light .text-white\/20 { color: #cbd5e1; }
    html.light .hover\:text-white:hover { color: #0f172a; }

    /* Los botones con degradado de marca conservan su texto blanco */
    html.light .bg-gradient-to-r.text-white, html.light .bg-gradient-to-br.text-white { color: #fff; }

    /* Acentos de marca (tonos oscurecidos para contraste sobre fondo claro) */
    html.light .text-blue-200 { color: #1d4ed8; }
    html.light .text-blue-300, html.light .text-blue-400 { color: #2563eb; }
    html.light .hover\:text-blue-200:hover { color: #1e40af; }
    html.light .text-purple-200, html.light .text-purple-300, html.light .text-purple-400 { color: #7c3aed; }
    html.light .hover\:text-purple-200:hover { color: #6d28d9; }
    html.light .text-green-200 { color: #047857; }
    html.light .text-green-300, html.light .text-green-400 { color: #059669; }
    html.light .text-green-400\/60 { color: rgba(5, 150, 105, .8); }
    html.light .text-red-200 { color: #b91c1c; }
    html.light .text-red-300, html.light .text-red-400 { color: #dc2626; }
    html.light .text-yellow-200 { color: #b45309; }
    html.light .text-yellow-300, html.light .text-yellow-400 { color: #d97706; }
    html.light .text-gray-300 { color: #64748b; }
    html.light .text-gray-400, html.light .text-gray-500 { color: #94a3b8; }
    html.light .text-gray-600 { color: #475569; }

    /* Fondos de chips y superficies translúcidas */
    html.light .bg-blue-500\/20 { background-color: #dbeafe; }
    html.light .bg-blue-500\/30 { background-color: #bfdbfe; }
    html.light .hover\:bg-blue-500\/30:hover { background-color: #bfdbfe; }
    html.light .bg-purple-500\/20 { background-color: #ede9fe; }
    html.light .bg-purple-500\/30 { background-color: #ddd6fe; }
    html.light .hover\:bg-purple-500\/30:hover { background-color: #ddd6fe; }
    html.light .bg-green-500\/10, html.light .bg-green-400\/10 { background-color: #ecfdf5; }
    html.light .bg-green-500\/20 { background-color: #d1fae5; }
    html.light .bg-green-500\/30 { background-color: #a7f3d0; }
    html.light .hover\:bg-green-500\/30:hover { background-color: #a7f3d0; }
    html.light .bg-red-500\/10 { background-color: #fef2f2; }
    html.light .bg-red-500\/20 { background-color: #fee2e2; }
    html.light .bg-red-500\/30 { background-color: #fecaca; }
    html.light .hover\:bg-red-500\/30:hover { background-color: #fecaca; }
    html.light .bg-yellow-400\/10, html.light .bg-yellow-500\/10 { background-color: #fffbeb; }
    html.light .bg-yellow-500\/20 { background-color: #fef3c7; }
    html.light .bg-yellow-500\/30 { background-color: #fde68a; }
    html.light .hover\:bg-yellow-500\/30:hover { background-color: #fde68a; }
    html.light .bg-gray-500\/20 { background-color: rgba(226, 232, 240, .9); }

    /* Superficies blancas translúcidas (filas, tabs, pistas de progreso) */
    html.light .bg-white\/5 { background-color: rgba(15, 23, 42, .05); }
    html.light .bg-white\/10 { background-color: rgba(148, 163, 184, .18); }
    html.light .bg-white\/20 { background-color: rgba(148, 163, 184, .28); }
    html.light .hover\:bg-white\/5:hover { background-color: rgba(15, 23, 42, .08); }
    html.light .hover\:bg-white\/10:hover { background-color: rgba(148, 163, 184, .26); }
    html.light .hover\:bg-white\/20:hover { background-color: rgba(148, 163, 184, .35); }

    /* Bordes */
    html.light .border-white\/5 { border-color: rgba(148, 163, 184, .22); }
    html.light .border-white\/10 { border-color: rgba(148, 163, 184, .26); }
    html.light .border-white\/20 { border-color: rgba(148, 163, 184, .34); }
    html.light .border-white\/30 { border-color: rgba(148, 163, 184, .42); }
    html.light .border-green-500\/30 { border-color: #a7f3d0; }
    html.light .border-red-500\/30 { border-color: #fecaca; }
    html.light .border-blue-500\/30 { border-color: #bfdbfe; }
    html.light .border-yellow-500\/30 { border-color: #fde68a; }

    /* Botón de alternancia de tema */
    .theme-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.75rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: rgba(255, 255, 255, 0.7);
        cursor: pointer;
        transition: background .2s, color .2s, border-color .2s, transform .2s;
    }
    .theme-toggle:hover { color: #fff; background: rgba(255, 255, 255, 0.14); }
    .theme-toggle:active { transform: scale(0.95); }
    html.light .theme-toggle { background: rgba(255, 255, 255, 0.7); border-color: rgba(100, 116, 139, 0.3); color: #475569; }
    html.light .theme-toggle:hover { color: #0f172a; background: rgba(255, 255, 255, 0.92); }
    .theme-icon-sun { display: inline; }
    .theme-icon-moon { display: none; }
    html.light .theme-icon-sun { display: none; }
    html.light .theme-icon-moon { display: inline; }
</style>