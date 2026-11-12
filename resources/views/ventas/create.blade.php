@extends('layouts.app')

@section('title', 'Nueva Venta')

@section('content')
    <div class="max-w-5xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('ventas.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white">Nueva Venta</h1>
                <p class="text-white/50 text-sm">Venta por valor total · no descuenta inventario</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="glass-card rounded-xl p-4 mb-6 border border-red-500/30 bg-red-500/10">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-400 mt-0.5"></i>
                    <ul class="text-sm text-red-200 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('ventas.store') }}" id="venta-form">
            @csrf

            <div class="glass rounded-2xl p-6 mb-6">
                <h2 class="text-lg font-semibold text-white mb-5">Datos de la venta</h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div class="sm:col-span-2">
                        <label for="cliente_id" class="block text-sm font-medium text-white/80 mb-2">Cliente *</label>
                        <select id="cliente_id" name="cliente_id" required class="input-glass bg-gray-900/60">
                            <option value="">Seleccionar cliente...</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected((string) old('cliente_id') === (string) $cliente->id)>
                                    {{ $cliente->nombre }}{{ $cliente->local ? ' — '.$cliente->local : '' }}{{ $cliente->ciudad ? ' ('.$cliente->ciudad.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('cliente_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="fecha" class="block text-sm font-medium text-white/80 mb-2">Fecha *</label>
                        <input type="date" id="fecha" name="fecha" required
                               value="{{ old('fecha', now()->toDateString()) }}" class="input-glass">
                        @error('fecha') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div id="vencimiento-container">
                        <label for="fecha_vencimiento" class="block text-sm font-medium text-white/80 mb-2">
                            Vencimiento del saldo
                        </label>
                        <input type="date" id="fecha_vencimiento" name="fecha_vencimiento"
                               value="{{ old('fecha_vencimiento', now()->addDays(30)->toDateString()) }}" class="input-glass">
                        @error('fecha_vencimiento') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2 lg:col-span-4">
                        <label for="observaciones" class="block text-sm font-medium text-white/80 mb-2">Descripción / observaciones</label>
                        <textarea id="observaciones" name="observaciones" rows="2" class="input-glass"
                                  placeholder="Ej: pedido de condimentos surtidos (opcional)">{{ old('observaciones') }}</textarea>
                        @error('observaciones') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="glass rounded-2xl p-6 mb-6">
                <h2 class="text-lg font-semibold text-white mb-5">Valor y pago</h2>
                <div class="grid sm:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div>
                            <label for="total" class="block text-sm font-medium text-white/80 mb-2">Valor total de la venta *</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-white/50 text-xl font-semibold">$</span>
                                <input type="number" id="total" name="total" required min="0.01" step="0.01" inputmode="decimal"
                                       value="{{ old('total') }}" placeholder="0"
                                       class="input-glass text-2xl font-bold pl-10 py-3">
                            </div>
                            @error('total') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center justify-between border-t border-white/10 pt-3">
                            <span class="text-white font-semibold">Total</span>
                            <span id="resumen-total" class="text-white text-2xl font-bold">$0</span>
                        </div>

                        <div class="mt-4 pt-4 border-t border-white/10 space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-white/60">Abonado</span>
                                <span id="resumen-abono" class="text-green-300 font-medium">$0</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-white/60">Saldo pendiente</span>
                                <span id="resumen-saldo" class="text-yellow-300 font-medium">$0</span>
                            </div>
                            <p id="vencimiento-hint" class="hidden text-xs text-yellow-300/80">
                                Si queda saldo, la fecha de vencimiento es obligatoria.
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <span class="block text-sm font-medium text-white/80 mb-2">Forma de pago</span>
                            <div class="inline-flex p-1 rounded-xl bg-white/5 border border-white/10" id="forma-pago-switch">
                                <label class="cursor-pointer select-none">
                                    <input type="radio" name="forma_pago" value="contado"
                                           class="peer sr-only forma-pago-radio"
                                           {{ old('forma_pago', 'contado') === 'contado' ? 'checked' : '' }}>
                                    <span class="block px-6 py-2 rounded-lg text-sm font-medium text-white/60 transition-all peer-checked:text-white peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-purple-600 peer-checked:shadow-lg peer-checked:shadow-blue-500/30">
                                        <i class="fas fa-money-bill-wave mr-1"></i> Contado
                                    </span>
                                </label>
                                <label class="cursor-pointer select-none">
                                    <input type="radio" name="forma_pago" value="credito"
                                           class="peer sr-only forma-pago-radio"
                                           {{ old('forma_pago', 'contado') === 'credito' ? 'checked' : '' }}>
                                    <span class="block px-6 py-2 rounded-lg text-sm font-medium text-white/60 transition-all peer-checked:text-white peer-checked:bg-gradient-to-r peer-checked:from-blue-500 peer-checked:to-purple-600 peer-checked:shadow-lg peer-checked:shadow-blue-500/30">
                                        <i class="fas fa-credit-card mr-1"></i> Crédito
                                    </span>
                                </label>
                            </div>
                            <p class="text-xs text-white/40 mt-2" id="forma-pago-ayuda">
                                Contado: se cobra el 100% del total.
                            </p>
                        </div>

                        <div id="abono-container">
                            <label for="abono_inicial" class="block text-sm font-medium text-white/80 mb-2">Abono inicial</label>
                            <input type="number" id="abono_inicial" name="abono_inicial" min="0" step="0.01"
                                   value="{{ old('abono_inicial', 0) }}" class="input-glass">
                            @error('abono_inicial') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="metodo_pago" class="block text-sm font-medium text-white/80 mb-2">Método de pago</label>
                            <select id="metodo_pago" name="metodo_pago" class="input-glass bg-gray-900/60">
                                @foreach ($metodos as $key => $label)
                                    <option value="{{ $key }}" @selected(old('metodo_pago', 'efectivo') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="referencia_pago" class="block text-sm font-medium text-white/80 mb-2">Referencia del pago</label>
                            <input type="text" id="referencia_pago" name="referencia_pago" maxlength="100"
                                   value="{{ old('referencia_pago') }}" class="input-glass" placeholder="Opcional">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                    <i class="fas fa-save"></i>
                    Registrar Venta
                </button>
                <a href="{{ route('ventas.index') }}" class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const totalInput = document.getElementById('total');
            const formaPagoRadios = document.querySelectorAll('input[name="forma_pago"]');
            const abonoInput = document.getElementById('abono_inicial');
            const vencimientoInput = document.getElementById('fecha_vencimiento');
            const vencimientoContainer = document.getElementById('vencimiento-container');
            const vencimientoHint = document.getElementById('vencimiento-hint');
            const abonoContainer = document.getElementById('abono-container');
            const formaPagoAyuda = document.getElementById('forma-pago-ayuda');

            const money = new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                maximumFractionDigits: 0,
            });

            const fmt = (value) => money.format(isFinite(value) ? value : 0);

            const esContado = () => {
                const seleccionado = document.querySelector('input[name="forma_pago"]:checked');
                return seleccionado && seleccionado.value === 'contado';
            };

            formaPagoRadios.forEach((radio) => {
                radio.addEventListener('change', () => {
                    if (!radio.checked) return;

                    if (radio.value === 'contado') {
                        // Contado: se cobra el total, sin vencimiento ni abono manual
                        abonoInput.value = '';
                        abonoInput.disabled = true;
                        abonoContainer.style.display = 'none';
                        vencimientoInput.value = '';
                        vencimientoInput.removeAttribute('required');
                        vencimientoContainer.style.display = 'none';
                        vencimientoHint.classList.add('hidden');
                        formaPagoAyuda.textContent = 'Contado: se cobra el 100% del total.';
                    } else {
                        // Crédito: abono opcional + vencimiento si queda saldo
                        abonoInput.disabled = false;
                        abonoContainer.style.display = '';
                        vencimientoContainer.style.display = '';
                        if (!vencimientoInput.value) {
                            const en30 = new Date();
                            en30.setDate(en30.getDate() + 30);
                            vencimientoInput.value = en30.toISOString().slice(0, 10);
                        }
                        formaPagoAyuda.textContent = 'Crédito: abono inicial opcional y saldo con vencimiento.';
                    }

                    recalcular();
                });
            });

            function recalcular() {
                const total = Math.max(parseFloat(totalInput.value || '0'), 0);
                const abono = esContado() ? total : Math.max(parseFloat(abonoInput.value || '0'), 0);
                const saldo = Math.max(total - abono, 0);

                document.getElementById('resumen-total').textContent = fmt(total);
                document.getElementById('resumen-abono').textContent = fmt(abono);
                document.getElementById('resumen-saldo').textContent = fmt(saldo);

                if (esContado()) {
                    vencimientoHint.classList.add('hidden');
                    vencimientoInput.removeAttribute('required');
                    return;
                }

                vencimientoHint.classList.toggle('hidden', saldo <= 0);

                if (saldo > 0) {
                    vencimientoInput.setAttribute('required', 'required');
                } else {
                    vencimientoInput.removeAttribute('required');
                }
            }

            totalInput.addEventListener('input', recalcular);
            abonoInput.addEventListener('input', recalcular);

            // Inicializar forma de pago (dispara recalcular y ajusta vencimiento/abono)
            const radioInicial = document.querySelector('input[name="forma_pago"]:checked');

            if (radioInicial) {
                radioInicial.dispatchEvent(new Event('change'));
            } else {
                recalcular();
            }
        })();
    </script>
@endpush
