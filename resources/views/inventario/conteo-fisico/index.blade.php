@extends('layouts.app')

@section('title', 'Conteo Físico')

@section('content')
    <div class="flex items-center justify-between mb-8 animate-fade-in">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Conteo Físico</h1>
            <p class="text-white/60">Comparaciones entre el stock del sistema y el conteo real en bodega.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('inventario.conteo-fisico.create', ['tipo' => 'materia_prima']) }}"
               class="flex items-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-500/30 transition-colors">
                <i class="fas fa-seedling"></i>
                Contar Materia Prima
            </a>
            <a href="{{ route('inventario.conteo-fisico.create', ['tipo' => 'producto_terminado']) }}"
               class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                <i class="fas fa-box-open"></i>
                Contar Productos
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="glass-card rounded-2xl p-4 mb-6 animate-delay-100">
        <form method="GET" class="flex items-end gap-4">
            <div>
                <label class="block text-xs text-white/50 mb-1">Estado</label>
                <select name="estado" class="input-glass bg-gray-900/60" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    @foreach ($estados as $key => $label)
                        <option value="{{ $key }}" @selected(request('estado') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="flex items-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-3 rounded-xl font-medium hover:bg-blue-500/30 transition-colors">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            @if (request('estado'))
                <a href="{{ route('inventario.conteo-fisico.index') }}" class="text-white/50 hover:text-white text-sm py-3">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($conteos->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-clipboard-check text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay conteos físicos registrados.</p>
                <div class="mt-4 flex justify-center gap-3">
                    <a href="{{ route('inventario.conteo-fisico.create', ['tipo' => 'materia_prima']) }}" class="text-blue-300 hover:text-blue-200 text-sm">
                        Contar materia prima →</a>
                    <a href="{{ route('inventario.conteo-fisico.create', ['tipo' => 'producto_terminado']) }}" class="text-purple-300 hover:text-purple-200 text-sm">
                        Contar productos →</a>
                </div>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4">Código</th>
                        <th class="p-4">Tipo</th>
                        <th class="p-4">Fecha</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4 hidden md:table-cell">Detalles</th>
                        <th class="p-4 hidden md:table-cell">Creado por</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($conteos as $conteo)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="p-4 font-mono text-blue-300">{{ $conteo->codigo }}</td>
                            <td class="p-4 text-white/60 capitalize">{{ $conteo->tipo === 'materia_prima' ? 'Materia prima' : 'Producto terminado' }}</td>
                            <td class="p-4 text-white/60">{{ $conteo->fecha_conteo->format('d/m/Y') }}</td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    @switch($conteo->estado)
                                        @case('borrador') bg-gray-500/20 text-gray-300 @break
                                        @case('completado') bg-blue-500/20 text-blue-300 @break
                                        @case('aprobado') bg-green-500/20 text-green-300 @break
                                        @case('anulado') bg-red-500/20 text-red-300 @break
                                    @endswitch">
                                    {{ $estados[$conteo->estado] ?? $conteo->estado }}
                                </span>
                            </td>
                            <td class="p-4 hidden md:table-cell text-white/40">{{ $conteo->detalles_count }} líneas</td>
                            <td class="p-4 hidden md:table-cell text-white/40">{{ $conteo->user?->name ?? '—' }}</td>
                            <td class="p-4 text-right">
                                <a href="{{ route('inventario.conteo-fisico.show', $conteo) }}"
                                   class="inline-flex items-center gap-1 text-blue-300 hover:text-blue-200 text-xs">
                                    <i class="fas fa-eye"></i> Ver / Continuar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-4">
                {{ $conteos->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection
