@extends('layouts.app')

@section('title', 'Movimientos de Inventario')

@section('content')
    <div class="flex items-center justify-between mb-8 animate-fade-in">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Movimientos de Inventario</h1>
            <p class="text-white/60">Kardex global de entradas y salidas.</p>
        </div>
        <a href="{{ route('inventario.movimientos.create') }}"
           class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
            <i class="fas fa-arrows-rotate"></i>
            <span>Nuevo Movimiento</span>
        </a>
    </div>

    <!-- Filters -->
    <div class="glass-card rounded-2xl p-4 mb-6 animate-delay-100">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs text-white/50 mb-1">Tipo</label>
                <select name="tipo" class="input-glass bg-gray-900/60" onchange="this.form.submit()">
                    <option value="">Todos los tipos</option>
                    @foreach ($tipos as $key => $label)
                        <option value="{{ $key }}" @selected(request('tipo') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-white/50 mb-1">Buscar</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Documento, referencia, motivo..."
                       class="input-glass">
            </div>
            <button type="submit" class="flex items-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-3 rounded-xl font-medium hover:bg-blue-500/30 transition-colors">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            @if (request()->has('tipo') || request()->has('q'))
                <a href="{{ route('inventario.movimientos.index') }}" class="text-white/50 hover:text-white text-sm py-3">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($movimientos->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-arrows-rotate text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay movimientos registrados.</p>
                <a href="{{ route('inventario.movimientos.create') }}" class="inline-block mt-4 text-blue-300 hover:text-blue-200 text-sm">
                    Registrar el primero →</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4">Fecha</th>
                        <th class="p-4">Tipo</th>
                        <th class="p-4">Artículo</th>
                        <th class="p-4 text-right">Cantidad</th>
                        <th class="p-4 text-right">Saldo</th>
                        <th class="p-4">Documento</th>
                        <th class="p-4 hidden lg:table-cell">Usuario</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($movimientos as $mov)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="p-4 text-white/60 whitespace-nowrap">{{ $mov->fecha->format('d/m/Y H:i') }}</td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full text-xs whitespace-nowrap
                                    {{ $mov->direccion === 'entrada' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300' }}">
                                    {{ $tipos[$mov->tipo] ?? $mov->tipo }}
                                </span>
                            </td>
                            <td class="p-4 text-white font-medium">{{ $mov->origen?->nombre ?? '—' }}</td>
                            <td class="p-4 text-right font-medium {{ $mov->direccion === 'entrada' ? 'text-green-300' : 'text-red-300' }}">
                                {{ ($mov->direccion === 'entrada' ? '+' : '-') . number_format($mov->cantidad, 0) }}
                            </td>
                            <td class="p-4 text-right text-white">{{ number_format($mov->saldo, 0) }}</td>
                            <td class="p-4 text-white/40">{{ $mov->documento ?? '—' }}</td>
                            <td class="p-4 hidden lg:table-cell text-white/40">{{ $mov->user?->name ?? '—' }}</td>
                            <td class="p-4 text-right">
                                <a href="{{ route('inventario.movimientos.show', $mov) }}"
                                   class="inline-flex items-center gap-1 text-blue-300 hover:text-blue-200 text-xs">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-4">
                {{ $movimientos->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
