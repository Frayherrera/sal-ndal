@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-8 animate-fade-in">
        <h1 class="text-3xl font-bold text-white mb-2">
            Bienvenido, {{ Auth::user()->name ?? 'Usuario' }}
        </h1>
        <p class="text-white/60">Panel de inventario — Resumen de materia prima, producción y ventas.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <a href="{{ route('inventario.materia-prima.index') }}" class="glass-card rounded-2xl p-6 animate-fade-in hover:bg-white/10 transition-colors block">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-seedling text-blue-400 text-xl"></i>
                </div>
                @if ($materias_primas_bajas > 0)
                    <span class="text-xs text-yellow-400 bg-yellow-400/10 px-2 py-1 rounded-full">{{ $materias_primas_bajas }} baja</span>
                @endif
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ number_format($stock_materia_prima_kg, 2) }} kg</div>
            <div class="text-sm text-white/50">{{ $materias_primas_count }} materias primas</div>
        </a>

        <a href="{{ route('inventario.productos-terminados.index') }}" class="glass-card rounded-2xl p-6 animate-delay-100 hover:bg-white/10 transition-colors block">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-box-open text-purple-400 text-xl"></i>
                </div>
                @if ($productos_bajos > 0)
                    <span class="text-xs text-yellow-400 bg-yellow-400/10 px-2 py-1 rounded-full">{{ $productos_bajos }} bajo</span>
                @endif
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ number_format($productos_disponibles) }}</div>
            <div class="text-sm text-white/50">{{ $productos_count }} productos disponibles</div>
        </a>

        <a href="{{ route('inventario.movimientos.index') }}" class="glass-card rounded-2xl p-6 animate-delay-200 hover:bg-white/10 transition-colors block">
            <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center mb-4">
                <i class="fas fa-arrows-rotate text-green-400 text-xl"></i>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ number_format($movimientos_recientes->count()) }}</div>
            <div class="text-sm text-white/50">Movimientos recientes</div>
        </a>

        <a href="{{ route('inventario.alertas') }}" class="glass-card rounded-2xl p-6 animate-delay-300 hover:bg-white/10 transition-colors block">
            <div class="w-12 h-12 bg-red-500/20 rounded-xl flex items-center justify-center mb-4">
                <i class="fas fa-bell text-red-400 text-xl"></i>
            </div>
            <div class="text-3xl font-bold text-white mb-1">{{ $materias_primas_bajas + $productos_bajos }}</div>
            <div class="text-sm text-white/50">Artículos con stock bajo</div>
        </a>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Recent Activity -->
        <div class="lg:col-span-2 glass-card rounded-2xl p-6 animate-fade-in">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold text-white">Movimientos Recientes</h2>
                <a href="{{ route('inventario.movimientos.index') }}" class="text-sm text-blue-300 hover:text-blue-200 transition-colors">Ver todo</a>
            </div>

            @if ($movimientos_recientes->isEmpty())
                <div class="text-center py-12">
                    <i class="fas fa-arrows-rotate text-3xl text-white/20 mb-3"></i>
                    <p class="text-white/50">Aún no hay movimientos.</p>
                    <a href="{{ route('inventario.movimientos.create') }}" class="inline-block mt-3 text-blue-300 text-sm hover:text-blue-200">Registrar uno →</a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach ($movimientos_recientes as $mov)
                        <div class="flex items-center gap-4 p-3 rounded-xl hover:bg-white/5 transition-colors">
                            <div class="w-10 h-10 {{ $mov->direccion === 'entrada' ? 'bg-green-500/20' : 'bg-red-500/20' }} rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas {{ $mov->direccion === 'entrada' ? 'fa-arrow-down text-green-400' : 'fa-arrow-up text-red-400' }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-white">{{ $mov->origen?->nombre ?? 'N/D' }}
                                    <span class="text-white/40">· {{ \App\Models\MovimientoInventario::TIPOS[$mov->tipo] ?? $mov->tipo }}</span>
                                </div>
                                <div class="text-xs text-white/40">{{ $mov->fecha->diffForHumans() }}</div>
                            </div>
                            <span class="text-sm font-medium {{ $mov->direccion === 'entrada' ? 'text-green-400' : 'text-red-400' }}">
                                {{ ($mov->direccion === 'entrada' ? '+' : '-') . number_format($mov->cantidad, 0) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="glass-card rounded-2xl p-6 animate-delay-200">
            <h2 class="text-lg font-semibold text-white mb-6">Acciones Rápidas</h2>

            <div class="space-y-3">
                <a href="{{ route('inventario.movimientos.create', ['tipo' => 'compra_recepcion']) }}"
                   class="flex items-center gap-3 p-3 rounded-xl bg-blue-500/20 hover:bg-blue-500/30 text-blue-300 transition-colors w-full text-left">
                    <i class="fas fa-seedling w-5 text-center"></i>
                    <span class="text-sm font-medium">Registrar Compra</span>
                </a>

                <a href="{{ route('inventario.produccion.create') }}"
                   class="flex items-center gap-3 p-3 rounded-xl bg-purple-500/20 hover:bg-purple-500/30 text-purple-300 transition-colors w-full text-left">
                    <i class="fas fa-box-open w-5 text-center"></i>
                    <span class="text-sm font-medium">Registrar Producción</span>
                </a>

                <a href="{{ route('inventario.movimientos.create', ['tipo' => 'venta_despacho']) }}"
                   class="flex items-center gap-3 p-3 rounded-xl bg-green-500/20 hover:bg-green-500/30 text-green-300 transition-colors w-full text-left">
                    <i class="fas fa-tag w-5 text-center"></i>
                    <span class="text-sm font-medium">Registrar Venta</span>
                </a>

                <a href="{{ route('inventario.conteo-fisico.create', ['tipo' => 'materia_prima']) }}"
                   class="flex items-center gap-3 p-3 rounded-xl bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-300 transition-colors w-full text-left">
                    <i class="fas fa-clipboard-check w-5 text-center"></i>
                    <span class="text-sm font-medium">Nuevo Conteo Físico</span>
                </a>

                <a href="{{ route('inventario.alertas') }}"
                   class="flex items-center gap-3 p-3 rounded-xl bg-red-500/20 hover:bg-red-500/30 text-red-300 transition-colors w-full text-left">
                    <i class="fas fa-bell w-5 text-center"></i>
                    <span class="text-sm font-medium">Ver Alertas de Stock</span>
                </a>
            </div>
        </div>
    </div>
@endsection
