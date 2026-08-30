@extends('layouts.app')

@section('title', $materia->nombre)

@section('content')
    <div class="flex items-center gap-3 mb-6 animate-fade-in">
        <a href="{{ route('inventario.materia-prima.index') }}" class="text-white/60 hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="flex-1">
            <h1 class="text-2xl font-bold text-white">{{ $materia->nombre }}</h1>
            <p class="text-white/50 text-sm font-mono">{{ $materia->codigo }}</p>
        </div>
        <a href="{{ route('inventario.materia-prima.edit', $materia) }}"
           class="flex items-center gap-2 bg-purple-500/20 text-purple-300 px-4 py-2 rounded-xl font-medium hover:bg-purple-500/30 transition-colors">
            <i class="fas fa-edit"></i> Editar
        </a>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8 animate-delay-100">
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-3">
                <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-box text-blue-400"></i>
                </div>
            </div>
            <div class="text-2xl font-bold text-white">{{ number_format($materia->stock_kg(), $materia->unidad_base === 'g' ? 0 : 2) }} {{ $materia->unidad_base }}</div>
            <div class="text-xs text-white/50 mt-1">Stock actual</div>
        </div>

        <div class="glass-card rounded-2xl p-5">
            <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center mb-3">
                <i class="fas fa-triangle-exclamation text-purple-400"></i>
            </div>
            <div class="text-2xl font-bold text-white">{{ number_format($materia->stock_minimo, 2) }} {{ $materia->unidad_base }}</div>
            <div class="text-xs text-white/50 mt-1">Stock mínimo</div>
        </div>

        <div class="glass-card rounded-2xl p-5">
            <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center mb-3">
                <i class="fas fa-tag text-green-400"></i>
            </div>
            <div class="text-xl font-bold text-white">${{ number_format($materia->inventario?->costo_promedio ?? 0, 2) }}</div>
            <div class="text-xs text-white/50 mt-1">Costo promedio / kg</div>
        </div>

        <div class="glass-card rounded-2xl p-5">
            <div class="w-10 h-10 {{ $materia->esStockBajo() ? 'bg-yellow-500/20' : 'bg-green-500/20' }} rounded-lg flex items-center justify-center mb-3">
                <i class="fas {{ $materia->esStockBajo() ? 'fa-exclamation text-yellow-400' : 'fa-check text-green-400' }}"></i>
            </div>
            <div class="text-xl font-bold {{ $materia->esStockBajo() ? 'text-yellow-300' : 'text-green-300' }}">
                {{ $materia->esStockBajo() ? 'Bajo' : 'OK' }}
            </div>
            <div class="text-xs text-white/50 mt-1">Estado del stock</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Details -->
        <div class="glass-card rounded-2xl p-6 animate-delay-200">
            <h2 class="text-lg font-semibold text-white mb-4">Detalles</h2>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-white/50">Categoría</dt><dd class="text-white">{{ $materia->categoria ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-white/50">Proveedor</dt><dd class="text-white">{{ $materia->proveedor ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-white/50">Ubicación</dt><dd class="text-white">{{ $materia->ubicacion ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-white/50">Status</dt><dd class="{{ $materia->activo ? 'text-green-300' : 'text-gray-300' }}">{{ $materia->activo ? 'Activo' : 'Inactivo' }}</dd></div>
            </dl>
            <div class="mt-6">
                <a href="{{ route('inventario.movimientos.create', ['tipo' => 'compra_recepcion']) }}"
                   class="w-full flex items-center justify-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-2.5 rounded-xl font-medium hover:bg-blue-500/30 transition-colors">
                    <i class="fas fa-arrow-down"></i> Registrar Compra
                </a>
            </div>
        </div>

        <!-- Kardex -->
        <div class="lg:col-span-2 glass-card rounded-2xl p-6 animate-delay-200">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white">Kardex de Movimientos</h2>
                <span class="text-xs text-white/40">{{ $materia->movimientos->count() }} registros</span>
            </div>

            @if ($materia->movimientos->isEmpty())
                <div class="text-center py-12">
                    <i class="fas fa-arrows-rotate text-3xl text-white/20 mb-3"></i>
                    <p class="text-white/50 text-sm">Aún no hay movimientos para esta materia prima.</p>
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
                            @foreach ($materia->movimientos as $mov)
                                <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                    <td class="py-2.5 pr-4 text-white/60 whitespace-nowrap">{{ $mov->fecha->format('d/m/Y H:i') }}</td>
                                    <td class="py-2.5 pr-4">
                                        <span class="px-2 py-0.5 rounded-full text-xs
                                            {{ $mov->direccion === 'entrada' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300' }}">
                                            {{ $mov->tipo }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-4 text-right font-medium {{ $mov->direccion === 'entrada' ? 'text-green-300' : 'text-red-300' }}">
                                        {{ ($mov->direccion === 'entrada' ? '+' : '-') . number_format($mov->cantidad / 1000, 2) }}
                                    </td>
                                    <td class="py-2.5 pr-4 text-right text-white">{{ number_format($mov->saldo / 1000, 2) }}</td>
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
