{{--
    Buscador en vivo para listados. Requiere $total y $nombre (plural, p. ej. 'productos').
    Opcional $placeholder. Las filas a filtrar llevan class="fila-buscable" y
    data-buscar (texto en minúsculas y sin tildes); los enlaces de orden llevan
    class="orden-link" para conservar la búsqueda, y el contenedor de la tabla
    incluye un elemento #sinResultados (oculto) para cuando no hay coincidencias.
--}}
<div class="glass-card rounded-2xl p-3 mb-4 flex flex-col sm:flex-row sm:items-center gap-3 animate-delay-100">
    <div class="relative flex-1">
        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-white/40"></i>
        <input type="search" id="buscador" value="{{ request('q') }}" autocomplete="off"
               class="input-glass pl-11" placeholder="{{ $placeholder ?? 'Buscar…' }}" aria-label="{{ $placeholder ?? 'Buscar' }}">
    </div>
    <span id="contador" class="text-sm text-white/50 whitespace-nowrap" data-nombre="{{ $nombre }}">{{ $total }} {{ $nombre }}</span>
</div>

@push('scripts')
<script>
(function () {
    // Filtra sin distinguir tildes ni mayúsculas; todas las palabras deben aparecer
    var buscador = document.getElementById('buscador');
    if (!buscador) return;

    var filas = Array.prototype.slice.call(document.querySelectorAll('.fila-buscable'));
    var contador = document.getElementById('contador');
    var nombre = contador.dataset.nombre;
    var sinResultados = document.getElementById('sinResultados');
    var normalizar = function (t) {
        return t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
    };

    var filtrar = function () {
        var palabras = normalizar(buscador.value).split(/\s+/).filter(Boolean);
        var visibles = 0;
        filas.forEach(function (fila) {
            var ok = palabras.every(function (p) { return fila.dataset.buscar.indexOf(p) !== -1; });
            fila.style.display = ok ? '' : 'none';
            if (ok) visibles++;
        });
        contador.textContent = visibles === filas.length
            ? filas.length + ' ' + nombre
            : visibles + ' de ' + filas.length + ' ' + nombre;
        if (sinResultados) sinResultados.classList.toggle('hidden', visibles > 0);

        // Conserva la búsqueda en la URL y en los enlaces de orden
        var q = buscador.value.trim();
        var url = new URL(window.location.href);
        q ? url.searchParams.set('q', q) : url.searchParams.delete('q');
        history.replaceState(null, '', url);
        document.querySelectorAll('.orden-link').forEach(function (a) {
            var destino = new URL(a.href);
            q ? destino.searchParams.set('q', q) : destino.searchParams.delete('q');
            a.href = destino;
        });
    };

    buscador.addEventListener('input', filtrar);
    buscador.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { buscador.value = ''; filtrar(); }
    });
    if (buscador.value) filtrar();
})();
</script>
@endpush
