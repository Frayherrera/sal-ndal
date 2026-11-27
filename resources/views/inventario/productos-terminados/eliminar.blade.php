@extends('layouts.app')

@section('title', 'Eliminar '.$producto->nombre)

@section('content')
    @php use App\Support\Paquetes; @endphp
    <div class="max-w-2xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('inventario.productos-terminados.show', $producto) }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white">Eliminar producto</h1>
                <p class="text-white/50 text-sm">{{ $producto->nombre }} · {{ $producto->presentaciones->count() }} {{ $producto->presentaciones->count() === 1 ? 'presentación' : 'presentaciones' }}</p>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-5 mb-6">
            <div class="text-xs text-white/50 mb-3">Presentaciones que se borrarán</div>
            <ul class="space-y-2">
                @foreach ($producto->presentaciones as $pt)
                    <li class="flex items-center justify-between text-sm">
                        <span class="text-white">{{ $pt->etiqueta() }} <span class="text-white/40 font-mono text-xs ml-1">{{ $pt->codigo }}</span></span>
                        <span class="text-white/70">{{ Paquetes::texto($pt->stock_disponible()) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($resumen['en_ruta'])
            <div class="glass rounded-2xl p-8 border border-red-500/30 bg-red-500/5">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-truck text-red-400 text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-red-200 mb-1">No se puede eliminar ahora</h2>
                        <p class="text-sm text-white/60">
                            Este producto va cargado en el viaje <span class="font-mono">{{ $resumen['en_ruta'] }}</span>, que está en ruta.
                            Cierra o anula el viaje y vuelve a intentarlo.
                        </p>
                        <a href="{{ route('camion.index') }}" class="inline-block mt-4 text-blue-300 hover:text-blue-200 text-sm">Ir al camión →</a>
                    </div>
                </div>
            </div>
        @else
            <div class="glass rounded-2xl p-8">
                @if ($resumen['historial'] || $resumen['stock'] !== 0)
                    <div class="flex items-start gap-3 mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30">
                        <i class="fas fa-triangle-exclamation text-red-400 mt-0.5"></i>
                        <div class="text-sm text-red-100">
                            <p class="font-semibold mb-2">Se borrará todo el historial de este producto y no se puede deshacer:</p>
                            <ul class="list-disc pl-5 space-y-1 text-red-100/80">
                                @if ($resumen['stock'] !== 0)
                                    <li>Stock en bodega: {{ Paquetes::texto($resumen['stock']) }}</li>
                                @endif
                                @if ($resumen['movimientos'])
                                    <li>{{ $resumen['movimientos'] }} {{ $resumen['movimientos'] === 1 ? 'movimiento' : 'movimientos' }} del kardex</li>
                                @endif
                                @if ($resumen['viajes'])
                                    <li>Sus líneas en {{ $resumen['viajes'] }} {{ $resumen['viajes'] === 1 ? 'viaje' : 'viajes' }} del camión (cambia lo vendido en esos viajes y el reporte semanal)</li>
                                @endif
                                @if ($resumen['ventas'])
                                    <li>Sus líneas en {{ $resumen['ventas'] }} {{ $resumen['ventas'] === 1 ? 'venta antigua' : 'ventas antiguas' }} (el total de la venta no cambia)</li>
                                @endif
                                @if ($resumen['conteos'])
                                    <li>{{ $resumen['conteos'] }} {{ $resumen['conteos'] === 1 ? 'línea' : 'líneas' }} de conteos físicos</li>
                                @endif
                            </ul>
                            <p class="mt-3 text-red-100/70">Si solo quieres dejar de usarlo, mejor desactívalo: se oculta de los selectores y conserva el historial.</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-white/60 mb-6">Este producto no tiene stock ni historial. Se borrará con sus presentaciones y su receta.</p>
                @endif

                <form method="POST" action="{{ route('inventario.productos-terminados.destroy', $producto) }}">
                    @csrf
                    @method('DELETE')

                    @if ($resumen['historial'])
                        <label class="flex items-start gap-3 text-sm text-white/80 cursor-pointer">
                            <input type="checkbox" name="confirmar" value="1" class="mt-1 w-4 h-4 accent-red-500" required>
                            <span>Entiendo que se borrará el historial de «{{ $producto->nombre }}» y que no se puede recuperar.</span>
                        </label>
                        @error('confirmar') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    @endif

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <button type="submit"
                                class="flex items-center gap-2 bg-gradient-to-r from-red-500 to-rose-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-red-600 hover:to-rose-700 transition-all shadow-lg shadow-red-500/30"
                                onclick="return confirm('¿Eliminar «{{ $producto->nombre }}» definitivamente?')">
                            <i class="fas fa-trash"></i>
                            Eliminar definitivamente
                        </button>
                        @if ($producto->activo)
                            <button type="submit" form="formDesactivar" class="text-white/70 hover:text-white px-4 py-3 transition-colors text-sm">
                                <i class="fas fa-eye-slash mr-1"></i> Mejor desactivarlo
                            </button>
                        @endif
                        <a href="{{ route('inventario.productos-terminados.show', $producto) }}" class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                    </div>
                </form>
                @if ($producto->activo)
                    <form id="formDesactivar" method="POST" action="{{ route('inventario.productos-terminados.toggle', $producto) }}" class="hidden">
                        @csrf
                    </form>
                @endif
            </div>
        @endif
    </div>
@endsection
