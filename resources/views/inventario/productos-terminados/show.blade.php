@extends('layouts.app')

@section('title', $producto->nombre)

@section('content')
    <div class="flex items-center gap-3 mb-6 animate-fade-in">
        <a href="{{ route('inventario.productos-terminados.index') }}" class="text-white/60 hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="flex-1">
            <h1 class="text-2xl font-bold text-white">{{ $producto->nombre }}</h1>
            <p class="text-white/50 text-sm font-mono">{{ $producto->codigo }}</p>
        </div>
        <a href="{{ route('inventario.productos-terminados.edit', $producto) }}"
           class="flex items-center gap-2 bg-purple-500/20 text-purple-300 px-4 py-2 rounded-xl font-medium hover:bg-purple-500/30 transition-colors">
            <i class="fas fa-edit"></i> Editar
        </a>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 animate-delay-100">
        <div class="glass-card rounded-2xl p-5">
            <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center mb-3">
                <i class="fas fa-box text-blue-400"></i>
            </div>
            <div class="text-2xl font-bold text-white">{{ number_format($producto->stock_disponible()) }}</div>
            <div class="text-xs text-white/50 mt-1">Disponibles (u)</div>
        </div>

        <div class="glass-card rounded-2xl p-5">
            <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center mb-3">
                <i class="fas fa-boxes-stacked text-purple-400"></i>
            </div>
            <div class="text-2xl font-bold text-white">{{ number_format($producto->stock_comprometido()) }}</div>
            <div class="text-xs text-white/50 mt-1">Comprometidos (u)</div>
        </div>

        <div class="glass-card rounded-2xl p-5">
            <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center mb-3">
                <i class="fas fa-tag text-green-400"></i>
            </div>
            <div class="text-2xl font-bold text-white">${{ number_format($producto->precio_venta, 2) }}</div>
            <div class="text-xs text-white/50 mt-1">Precio de venta</div>
        </div>

        <div class="glass-card rounded-2xl p-5">
            <div class="w-10 h-10 {{ $producto->esStockBajo() ? 'bg-yellow-500/20' : 'bg-green-500/20' }} rounded-lg flex items-center justify-center mb-3">
                <i class="fas {{ $producto->esStockBajo() ? 'fa-exclamation text-yellow-400' : 'fa-check text-green-400' }}"></i>
            </div>
            <div class="text-xl font-bold {{ $producto->esStockBajo() ? 'text-yellow-300' : 'text-green-300' }}">
                {{ $producto->esStockBajo() ? 'Bajo' : 'OK' }}
            </div>
            <div class="text-xs text-white/50 mt-1">Mínimo: {{ number_format($producto->stock_minimo, 0) }} u</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Details -->
        <div class="glass-card rounded-2xl p-6 animate-delay-200">
            <h2 class="text-lg font-semibold text-white mb-4">Detalles</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-white/50">Categoría</dt><dd class="text-white">{{ $producto->categoria ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-white/50">Presentación</dt><dd class="text-white capitalize">{{ $producto->presentacion ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-white/50">Peso neto</dt><dd class="text-white">{{ number_format($producto->peso_neto, 0) }} g</dd></div>
                <div class="flex justify-between"><dt class="text-white/50">Status</dt><dd class="{{ $producto->activo ? 'text-green-300' : 'text-gray-300' }}">{{ $producto->activo ? 'Activo' : 'Inactivo' }}</dd></div>
            </dl>
            <div class="mt-6 space-y-2">
                <a href="{{ route('inventario.produccion.create') }}"
                   class="w-full flex items-center justify-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-2.5 rounded-xl font-medium hover:bg-blue-500/30 transition-colors">
                    <i class="fas fa-box-open"></i> Registrar Producción
                </a>
                <a href="{{ route('inventario.movimientos.create', ['tipo' => 'venta_despacho']) }}"
                   class="w-full flex items-center justify-center gap-2 bg-green-500/20 text-green-300 px-4 py-2.5 rounded-xl font-medium hover:bg-green-500/30 transition-colors">
                    <i class="fas fa-arrow-up"></i> Registrar Venta
                </a>
            </div>

            <div class="mt-6 border-t border-white/10 pt-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-white/80">Receta (por unidad)</h3>
                    <a href="{{ route('inventario.recetas.edit', $producto) }}"
                       class="text-xs text-purple-300 hover:text-purple-200 transition-colors">
                        <i class="fas fa-edit"></i> {{ $producto->receta->isNotEmpty() ? 'Editar' : 'Definir' }}
                    </a>
                </div>
                @if ($producto->receta->isNotEmpty())
                    <ul class="space-y-2 text-sm">
                        @foreach ($producto->receta as $linea)
                            <li class="flex justify-between">
                                <span class="text-white/70">{{ $linea->materiaPrima?->nombre ?? '—' }}</span>
                                <span class="text-white">{{ number_format($linea->gramos_por_unidad, 3) }} g</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-white/40 text-xs">Sin receta definida. Usa el módulo de Recetas para indicar qué materia prima consume al producir.</p>
                @endif
            </div>
        </div>

        <!-- Kardex -->
        <div class="lg:col-span-2 glass-card rounded-2xl p-6 animate-delay-200">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white">Kardex de Movimientos</h2>
                <span class="text-xs text-white/40">{{ $producto->movimientos->count() }} registros</span>
            </div>

            @if ($producto->movimientos->isEmpty())
                <div class="text-center py-12">
                    <i class="fas fa-arrows-rotate text-3xl text-white/20 mb-3"></i>
                    <p class="text-white/50 text-sm">Aún no hay movimientos para este producto.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-white/50 border-b border-white/10">
                                <th class="py-2 pr-4">Fecha</th>
                                <th class="py-2 pr-4">Tipo</th>
                                <th class="py-2 pr-4 text-right">Cantidad</th>
                                <th class="py-2 pr-4 text-right">Saldo</th>
                                <th class="py-2 pr-4">Documento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($producto->movimientos as $mov)
                                <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                    <td class="py-2.5 pr-4 text-white/60 whitespace-nowrap">{{ $mov->fecha->format('d/m/Y H:i') }}</td>
                                    <td class="py-2.5 pr-4">
                                        <span class="px-2 py-0.5 rounded-full text-xs
                                            {{ $mov->direccion === 'entrada' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300' }}">
                                            {{ $mov->tipo }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-4 text-right font-medium {{ $mov->direccion === 'entrada' ? 'text-green-300' : 'text-red-300' }}">
                                        {{ ($mov->direccion === 'entrada' ? '+' : '-') . number_format($mov->cantidad, 0) }}
                                    </td>
                                    <td class="py-2.5 pr-4 text-right text-white">{{ number_format($mov->saldo, 0) }}</td>
                                    <td class="py-2.5 text-white/40">{{ $mov->documento ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
