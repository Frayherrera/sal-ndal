@extends('layouts.app')

@section('title', 'Gastos')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Gastos</h1>
            <p class="text-white/60">Gastos operativos y compras de materia prima.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('gastos.categorias.index') }}"
               class="flex items-center gap-2 glass-card text-white/70 px-4 py-2.5 rounded-xl font-medium hover:text-white transition-colors">
                <i class="fas fa-tags"></i>
                <span>Categorías</span>
            </a>
            <a href="{{ route('gastos.export', request()->query()) }}"
               class="flex items-center gap-2 glass-card text-white/70 px-4 py-2.5 rounded-xl font-medium hover:text-white transition-colors">
                <i class="fas fa-file-csv"></i>
                <span>CSV</span>
            </a>
            <a href="{{ route('gastos.create') }}"
               class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                <i class="fas fa-plus"></i>
                <span>Nuevo Gasto</span>
            </a>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-6 animate-delay-100">
        <a href="{{ route('gastos.reportes.categorias') }}"
           class="flex items-center gap-2 text-sm text-blue-300 hover:text-blue-200 transition-colors">
            <i class="fas fa-chart-pie"></i> Gastos por categoría
        </a>
        <span class="text-white/20">|</span>
        <a href="{{ route('gastos.reportes.resultado') }}"
           class="flex items-center gap-2 text-sm text-purple-300 hover:text-purple-200 transition-colors">
            <i class="fas fa-scale-balanced"></i> Ventas − gastos
        </a>
    </div>

    <!-- Filtros -->
    <div class="glass-card rounded-2xl p-4 mb-6 animate-delay-100">
        <form method="GET" class="grid sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
            <div>
                <label class="block text-xs text-white/50 mb-1">Desde</label>
                <input type="date" name="desde" value="{{ request('desde') }}" class="input-glass">
            </div>
            <div>
                <label class="block text-xs text-white/50 mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ request('hasta') }}" class="input-glass">
            </div>
            <div>
                <label class="block text-xs text-white/50 mb-1">Categoría</label>
                <select name="categoria_id" class="input-glass bg-gray-900/60">
                    <option value="">Todas</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected((string) request('categoria_id') === (string) $categoria->id)>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-white/50 mb-1">Estado</label>
                <select name="estado" class="input-glass bg-gray-900/60">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Gasto::ESTADOS as $key => $label)
                        <option value="{{ $key }}" @selected(request('estado') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-white/50 mb-1">Buscar</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Número, descripción, proveedor..." class="input-glass">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 flex items-center justify-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-3 rounded-xl font-medium hover:bg-blue-500/30 transition-colors">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                @if (request()->hasAny(['desde', 'hasta', 'categoria_id', 'estado', 'q']))
                    <a href="{{ route('gastos.index') }}" class="text-white/50 hover:text-white text-sm py-3 px-1">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Resumen -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="glass-card rounded-2xl p-5 animate-delay-100">
            <div class="text-xs text-white/50 mb-1">Gastos registrados</div>
            <div class="text-2xl font-bold text-white">{{ number_format($resumen['count']) }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5 animate-delay-100">
            <div class="text-xs text-white/50 mb-1">Total gastado</div>
            <div class="text-2xl font-bold text-red-300">${{ number_format($resumen['total'], 0, ',', '.') }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5 animate-delay-200">
            <div class="text-xs text-white/50 mb-1">De compras de materia prima</div>
            <div class="text-2xl font-bold text-white">${{ number_format($resumen['automaticos'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($gastos->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-receipt text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay gastos registrados con estos filtros.</p>
                <a href="{{ route('gastos.create') }}" class="inline-block mt-4 text-blue-300 hover:text-blue-200 text-sm">
                    Registrar el primer gasto →</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4">Número</th>
                        <th class="p-4 hidden md:table-cell">Fecha</th>
                        <th class="p-4">Descripción</th>
                        <th class="p-4 hidden lg:table-cell">Categoría</th>
                        <th class="p-4 hidden sm:table-cell">Pago</th>
                        <th class="p-4 text-right">Valor</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($gastos as $gasto)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors {{ $gasto->estaAnulado() ? 'opacity-60' : '' }}">
                            <td class="p-4">
                                <div class="font-mono text-white/80">{{ $gasto->numero }}</div>
                                @if ($gasto->estaAnulado())
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs bg-white/10 text-white/50">Anulado</span>
                                @elseif ($gasto->esAutomatico())
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs bg-purple-500/20 text-purple-300" title="Generado desde una compra de inventario">
                                        <i class="fas fa-bolt mr-1"></i>Compra
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 text-white/60 whitespace-nowrap hidden md:table-cell">{{ $gasto->fecha->format('d/m/Y') }}</td>
                            <td class="p-4">
                                <div class="text-white font-medium {{ $gasto->estaAnulado() ? 'line-through' : '' }}">{{ $gasto->descripcion }}</div>
                                <div class="text-xs text-white/40">
                                    <span class="lg:hidden">{{ $gasto->categoria?->nombre }}</span>
                                    @if ($gasto->proveedor)
                                        <span class="lg:hidden">·</span> {{ $gasto->proveedor }}
                                    @endif
                                </div>
                            </td>
                            <td class="p-4 text-white/60 hidden lg:table-cell">{{ $gasto->categoria?->nombre ?? '—' }}</td>
                            <td class="p-4 text-white/60 hidden sm:table-cell">{{ $gasto->metodoPago() }}</td>
                            <td class="p-4 text-right font-medium whitespace-nowrap {{ $gasto->estaAnulado() ? 'text-white/40 line-through' : 'text-red-300' }}">
                                ${{ number_format($gasto->monto, 0, ',', '.') }}
                            </td>
                            <td class="p-4">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($gasto->esAutomatico())
                                        <a href="{{ route('inventario.movimientos.show', $gasto->movimiento_inventario_id) }}"
                                           class="w-9 h-9 inline-flex items-center justify-center rounded-lg glass text-purple-300 hover:text-purple-200"
                                           title="Ver la compra en inventario">
                                            <i class="fas fa-arrows-rotate text-sm"></i>
                                        </a>
                                    @elseif (! $gasto->estaAnulado())
                                        <a href="{{ route('gastos.edit', $gasto) }}"
                                           class="w-9 h-9 inline-flex items-center justify-center rounded-lg glass text-white/60 hover:text-white"
                                           title="Editar">
                                            <i class="fas fa-pen text-sm"></i>
                                        </a>
                                        <a href="{{ route('gastos.anular', $gasto) }}"
                                           class="w-9 h-9 inline-flex items-center justify-center rounded-lg glass text-red-300 hover:text-red-200 hover:bg-red-500/10"
                                           title="Anular">
                                            <i class="fas fa-ban text-sm"></i>
                                        </a>
                                    @else
                                        <a href="{{ route('gastos.anular', $gasto) }}"
                                           class="w-9 h-9 inline-flex items-center justify-center rounded-lg glass text-white/40 hover:text-white"
                                           title="Ver motivo de anulación">
                                            <i class="fas fa-circle-info text-sm"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-4">
                {{ $gastos->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
