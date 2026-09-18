@extends('layouts.app')

@section('title', 'Productos Terminados')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Productos Terminados</h1>
            <p class="text-white/60">Presentaciones comerciales empacadas y listas para la venta.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="glass-card rounded-xl p-1.5 flex items-center gap-1">
                <i class="fas fa-sort text-white/40 px-2"></i>
                <a href="{{ route('inventario.productos-terminados.index') }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ !request('sort') ? 'bg-white/10 text-white' : 'text-white/50 hover:bg-white/5' }}">
                    Nombre
                </a>
                <a href="{{ route('inventario.productos-terminados.index', ['sort' => 'stock', 'dir' => 'desc']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request('sort') === 'stock' && request('dir') !== 'asc' ? 'bg-blue-500/30 text-blue-200' : 'text-white/50 hover:bg-white/5' }}">
                    <i class="fas fa-caret-down"></i> Stock
                </a>
                <a href="{{ route('inventario.productos-terminados.index', ['sort' => 'stock', 'dir' => 'asc']) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request('sort') === 'stock' && request('dir') === 'asc' ? 'bg-purple-500/30 text-purple-200' : 'text-white/50 hover:bg-white/5' }}">
                    <i class="fas fa-caret-up"></i> Stock
                </a>
            </div>
            <a href="{{ route('inventario.productos-terminados.create') }}"
               class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                <i class="fas fa-plus"></i>
                <span>Nuevo Producto</span>
            </a>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($productos->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-box-open text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay productos terminados registrados.</p>
                <a href="{{ route('inventario.productos-terminados.create') }}" class="inline-block mt-4 text-blue-300 hover:text-blue-200 text-sm">
                    Registrar el primero →</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4 hidden sm:table-cell">Código</th>
                        <th class="p-4">Nombre</th>
                        <th class="p-4 hidden md:table-cell">Presentación</th>
                        <th class="p-4 text-right hidden md:table-cell">Peso neto</th>
                        <th class="p-4 text-right">Disponible</th>
                        <th class="p-4 text-right">Precio</th>
                        <th class="p-4 text-center hidden sm:table-cell">Estado</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($productos as $pt)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="p-4 font-mono text-blue-300 hidden sm:table-cell">{{ $pt->codigo }}</td>
                            <td class="p-4 text-white font-medium">{{ $pt->nombre }}</td>
                            <td class="p-4 text-white/60 capitalize hidden md:table-cell">{{ $pt->presentacion ?? '—' }}</td>
                            <td class="p-4 text-right text-white/60 hidden md:table-cell">{{ number_format($pt->peso_neto, 0) }} g</td>
                            <td class="p-4 text-right">
                                <span class="text-white font-semibold">{{ number_format($pt->stock_disponible()) }} u</span>
                                @if ($pt->esStockBajo())
                                    <span class="block text-xs text-yellow-400 mt-0.5">Bajo</span>
                                @endif
                            </td>
                            <td class="p-4 text-right text-white/80">${{ number_format($pt->precio_venta, 2) }}</td>
                            <td class="p-4 text-center hidden sm:table-cell">
                                <form method="POST" action="{{ route('inventario.productos-terminados.toggle', $pt) }}">
                                    @csrf
                                    <button type="submit"
                                            class="px-2 py-1 rounded-full text-xs font-medium {{ $pt->activo ? 'bg-green-500/20 text-green-300' : 'bg-gray-500/20 text-gray-300' }}">
                                        {{ $pt->activo ? 'Activo' : 'Inactivo' }}
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
                                            <a href="{{ route('inventario.productos-terminados.show', $pt) }}"
                                               class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:bg-white/10 transition-colors">
                                                <i class="fas fa-eye w-4 text-center text-blue-300"></i>
                                                Ver kardex
                                            </a>
                                            <a href="{{ route('inventario.productos-terminados.edit', $pt) }}"
                                               class="flex items-center gap-3 px-4 py-2.5 text-sm text-white/80 hover:bg-white/10 transition-colors">
                                                <i class="fas fa-edit w-4 text-center text-purple-300"></i>
                                                Editar
                                            </a>
                                            @if (!$pt->movimientos()->exists())
                                                <div class="border-t border-white/10">
                                                    <form method="POST" action="{{ route('inventario.productos-terminados.destroy', $pt) }}"
                                                          onsubmit="return confirm('¿Eliminar este producto?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-300 hover:bg-red-500/10 transition-colors">
                                                            <i class="fas fa-trash w-4 text-center"></i>
                                                            Eliminar
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
