@extends('layouts.app')

@section('title', 'Productos Terminados')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Productos Terminados</h1>
            <p class="text-white/60">Cada producto con sus presentaciones B2C y Especial; stock en paquetes.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="glass-card rounded-xl p-1.5 flex items-center gap-1">
                <i class="fas fa-sort text-white/40 px-2"></i>
                <a href="{{ route('inventario.productos-terminados.index') }}"
                   class="orden-link px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ !request('sort') ? 'bg-white/10 text-white' : 'text-white/50 hover:bg-white/5' }}">
                    Nombre
                </a>
                <a href="{{ route('inventario.productos-terminados.index', ['sort' => 'stock', 'dir' => 'desc']) }}"
                   class="orden-link px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request('sort') === 'stock' && request('dir') !== 'asc' ? 'bg-blue-500/30 text-blue-200' : 'text-white/50 hover:bg-white/5' }}">
                    <i class="fas fa-caret-down"></i> Stock
                </a>
                <a href="{{ route('inventario.productos-terminados.index', ['sort' => 'stock', 'dir' => 'asc']) }}"
                   class="orden-link px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request('sort') === 'stock' && request('dir') === 'asc' ? 'bg-purple-500/30 text-purple-200' : 'text-white/50 hover:bg-white/5' }}">
                    <i class="fas fa-caret-up"></i> Stock
                </a>
            </div>
            <a href="{{ route('inventario.productos-terminados.export-pdf') }}"
               class="flex items-center gap-2 glass-card text-white/70 px-4 py-2.5 rounded-xl font-medium hover:text-white transition-colors"
               title="Reporte PDF del inventario actual">
                <i class="fas fa-file-pdf"></i>
                <span>PDF</span>
            </a>
            <a href="{{ route('inventario.productos-terminados.create') }}"
               class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                <i class="fas fa-plus"></i>
                <span>Nuevo Producto</span>
            </a>
        </div>
    </div>

    @php
        use App\Models\ProductoTerminado;
        use App\Support\Paquetes;
    @endphp
    @if ($productos->isNotEmpty())
        @include('inventario._buscador', ['total' => $productos->count(), 'nombre' => 'productos', 'placeholder' => 'Buscar producto o código…'])
    @endif

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($productos->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-box-open text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay productos registrados.</p>
                <a href="{{ route('inventario.productos-terminados.create') }}" class="inline-block mt-4 text-blue-300 hover:text-blue-200 text-sm">
                    Registrar el primero →</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4">Producto</th>
                        @foreach (ProductoTerminado::LINEAS as $etiqueta)
                            <th class="p-4 text-right">{{ $etiqueta }}</th>
                        @endforeach
                        <th class="p-4 text-center hidden sm:table-cell">Estado</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($productos as $producto)
                        <tr class="fila-buscable border-b border-white/5 hover:bg-white/5 transition-colors {{ $producto->activo ? '' : 'opacity-60' }}"
                            data-buscar="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($producto->nombre.' '.$producto->presentaciones->pluck('codigo')->implode(' '))) }}">
                            <td class="p-4">
                                <a href="{{ route('inventario.productos-terminados.show', $producto) }}" class="text-white font-medium hover:text-blue-200">{{ $producto->nombre }}</a>
                                <span class="block text-xs text-white/40 mt-0.5">{{ $producto->presentaciones->count() }} {{ $producto->presentaciones->count() === 1 ? 'presentación' : 'presentaciones' }}</span>
                            </td>
                            @foreach (array_keys(ProductoTerminado::LINEAS) as $linea)
                                @php $deLinea = $producto->presentacionesDe($linea); @endphp
                                <td class="p-4 text-right align-top">
                                    @forelse ($deLinea as $pt)
                                        <div class="{{ $loop->first ? '' : 'mt-1.5' }} {{ $pt->activo ? '' : 'opacity-50' }}">
                                            @if ($deLinea->count() > 1)
                                                <span class="text-xs text-white/40">{{ rtrim(rtrim(number_format((float) $pt->peso_neto, 2, ',', '.'), '0'), ',') }} g:</span>
                                            @endif
                                            <span class="text-white font-semibold whitespace-nowrap">{{ Paquetes::texto($pt->stock_disponible()) }}</span>
                                            @if ($pt->activo && $pt->esStockBajo())
                                                <span class="block text-xs text-yellow-400">Bajo</span>
                                            @endif
                                        </div>
                                    @empty
                                        <span class="text-white/25">—</span>
                                    @endforelse
                                </td>
                            @endforeach
                            <td class="p-4 text-center hidden sm:table-cell">
                                <form method="POST" action="{{ route('inventario.productos-terminados.toggle', $producto) }}">
                                    @csrf
                                    <button type="submit"
                                            class="px-2 py-1 rounded-full text-xs font-medium {{ $producto->activo ? 'bg-green-500/20 text-green-300' : 'bg-gray-500/20 text-gray-300' }}">
                                        {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </form>
                            </td>
                            <td class="p-4 text-right">
                                <div class="relative inline-block dropdown">
                                    <button type="button" class="dropdown-toggle w-9 h-9 inline-flex items-center justify-center rounded-lg glass text-white/60 hover:text-white transition-colors"
                                            aria-haspopup="true" aria-expanded="false" title="Acciones">
                                        <i class="fas fa-ellipsis-vertical text-sm"></i>
                                    </button>
                                    <div class="dropdown-menu hidden absolute right-0 top-full mt-2 z-50 w-52 rounded-xl menu-glass overflow-hidden">
                                        <a href="{{ route('inventario.productos-terminados.show', $producto) }}"
                                           class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:bg-white/10 transition-colors">
                                            <i class="fas fa-eye w-4 text-center text-blue-300"></i>
                                            Ver detalle y kardex
                                        </a>
                                        <a href="{{ route('inventario.productos-terminados.edit', $producto) }}"
                                           class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:bg-white/10 transition-colors">
                                            <i class="fas fa-edit w-4 text-center text-purple-300"></i>
                                            Editar
                                        </a>
                                        @unless ($producto->presentaciones->contains(fn ($pt) => $pt->stock_disponible() !== 0 || $pt->movimientos_exists))
                                            <div class="border-t border-white/10">
                                                <form method="POST" action="{{ route('inventario.productos-terminados.destroy', $producto) }}"
                                                      onsubmit="return confirm('¿Eliminar este producto y sus presentaciones?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-300 hover:bg-red-500/10 transition-colors">
                                                        <i class="fas fa-trash w-4 text-center"></i>
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        @endunless
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div id="sinResultados" class="hidden text-center py-12">
                <i class="fas fa-search text-3xl text-white/20 mb-3"></i>
                <p class="text-white/60 text-sm">Ningún producto coincide con la búsqueda.</p>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
(function () {
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.dropdown').forEach(function (wrap) {
            var toggle = wrap.querySelector('.dropdown-toggle');
            var menu = wrap.querySelector('.dropdown-menu');

            if (wrap.contains(e.target)) {
                if (toggle.contains(e.target)) {
                    menu.classList.toggle('hidden');
                    toggle.setAttribute('aria-expanded', menu.classList.contains('hidden') ? 'false' : 'true');
                }
            } else {
                menu.classList.add('hidden');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.dropdown-menu').forEach(function (menu) {
                menu.classList.add('hidden');
            });
            document.querySelectorAll('.dropdown-toggle').forEach(function (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            });
        }
    });
})();
</script>
@endpush
