@extends('layouts.app')

@section('title', $producto->nombre)

@php use App\Support\Paquetes; @endphp

@section('content')
    <div class="flex items-center gap-3 mb-6 animate-fade-in">
        <a href="{{ route('inventario.productos-terminados.index') }}" class="text-white/60 hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="flex-1">
            <h1 class="text-2xl font-bold text-white">{{ $producto->nombre }}</h1>
            <p class="text-white/50 text-sm">
                {{ $producto->presentaciones->count() }} {{ $producto->presentaciones->count() === 1 ? 'presentación' : 'presentaciones' }}
                · <span class="{{ $producto->activo ? 'text-green-300' : 'text-gray-300' }}">{{ $producto->activo ? 'Activo' : 'Inactivo' }}</span>
            </p>
        </div>
        <a href="{{ route('inventario.productos-terminados.edit', $producto) }}"
           class="flex items-center gap-2 bg-purple-500/20 text-purple-300 px-4 py-2 rounded-xl font-medium hover:bg-purple-500/30 transition-colors">
            <i class="fas fa-edit"></i> Editar
        </a>
    </div>

    <!-- Una tarjeta por presentación -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8 animate-delay-100">
        @foreach ($producto->presentaciones as $pt)
            @php $bajo = $pt->esStockBajo(); $elegida = $seleccionada?->is($pt); @endphp
            <a href="{{ route('inventario.productos-terminados.show', [$producto, 'presentacion' => $pt->id]) }}"
               class="glass-card rounded-2xl p-5 block transition-all {{ $elegida ? 'ring-2 ring-blue-400/60' : 'hover:bg-white/10' }} {{ $pt->activo ? '' : 'opacity-60' }}">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $pt->categoria === 'B2C' ? 'bg-blue-500/20 text-blue-300' : 'bg-purple-500/20 text-purple-300' }}">{{ $pt->linea() }}</span>
                        <span class="text-white/60 text-sm ml-1">{{ rtrim(rtrim(number_format((float) $pt->peso_neto, 2, ',', '.'), '0'), ',') }} g</span>
                    </div>
                    <span class="text-xs font-mono text-white/40">{{ $pt->codigo }}</span>
                </div>
                <div class="text-2xl font-bold text-white">{{ Paquetes::texto($pt->stock_disponible()) }}</div>
                <div class="text-xs text-white/50 mt-1">{{ number_format($pt->stock_disponible(), 0, ',', '.') }} u disponibles</div>
                <div class="flex items-center justify-between mt-4 text-sm">
                    <span class="text-white/70">${{ number_format((float) $pt->precio_venta, 0, ',', '.') }} <span class="text-white/40 text-xs">/ paquete</span></span>
                    @if (! $pt->activo)
                        <span class="text-xs text-gray-300">Inactiva</span>
                    @else
                        <span class="text-xs {{ $bajo ? 'text-yellow-300' : 'text-green-300' }}">
                            <i class="fas {{ $bajo ? 'fa-exclamation' : 'fa-check' }}"></i>
                            {{ $bajo ? 'Bajo' : 'OK' }} · mín. {{ Paquetes::texto((int) $pt->stock_minimo) }}
                        </span>
                    @endif
                </div>
            </a>
        @endforeach
    </div>

    @if ($seleccionada)
        <div class="grid lg:grid-cols-3 gap-6">
            <!-- Presentación elegida: receta y acciones -->
            <div class="glass-card rounded-2xl p-6 animate-delay-200">
                <h2 class="text-lg font-semibold text-white mb-1">{{ $seleccionada->etiqueta() }}</h2>
                <p class="text-white/40 text-xs mb-4">Elige otra presentación en las tarjetas de arriba.</p>

                <div class="space-y-2">
                    <a href="{{ route('inventario.produccion.create') }}"
                       class="w-full flex items-center justify-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-2.5 rounded-xl font-medium hover:bg-blue-500/30 transition-colors">
                        <i class="fas fa-box-open"></i> Registrar Producción
                    </a>
                </div>

                <div class="mt-6 border-t border-white/10 pt-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-white/80">Receta (por unidad)</h3>
                        <a href="{{ route('inventario.recetas.edit', $seleccionada) }}"
                           class="text-xs text-purple-300 hover:text-purple-200 transition-colors">
                            <i class="fas fa-edit"></i> {{ $seleccionada->receta->isNotEmpty() ? 'Editar' : 'Definir' }}
                        </a>
                    </div>
                    @if ($seleccionada->receta->isNotEmpty())
                        <ul class="space-y-2 text-sm">
                            @foreach ($seleccionada->receta as $linea)
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

            <!-- Kardex de la presentación elegida -->
            <div class="lg:col-span-2 glass-card rounded-2xl p-6 animate-delay-200">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-white">Kardex · {{ $seleccionada->etiqueta() }}</h2>
                    <span class="text-xs text-white/40">{{ $movimientos->count() }} registros</span>
                </div>

                @if ($movimientos->isEmpty())
                    <div class="text-center py-12">
                        <i class="fas fa-arrows-rotate text-3xl text-white/20 mb-3"></i>
                        <p class="text-white/50 text-sm">Aún no hay movimientos para esta presentación.</p>
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
                                @foreach ($movimientos as $mov)
                                    <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                        <td class="py-2.5 pr-4 text-white/60 whitespace-nowrap">{{ $mov->fecha->format('d/m/Y H:i') }}</td>
                                        <td class="py-2.5 pr-4">
                                            <span class="px-2 py-0.5 rounded-full text-xs
                                                {{ $mov->direccion === 'entrada' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300' }}">
                                                {{ $mov->tipo }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 pr-4 text-right font-medium whitespace-nowrap {{ $mov->direccion === 'entrada' ? 'text-green-300' : 'text-red-300' }}">
                                            {{ ($mov->direccion === 'entrada' ? '+' : '−') . Paquetes::texto((int) $mov->cantidad) }}
                                        </td>
                                        <td class="py-2.5 pr-4 text-right text-white whitespace-nowrap">{{ Paquetes::texto((int) $mov->saldo) }}</td>
                                        <td class="py-2.5 text-white/40">{{ $mov->documento ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection
