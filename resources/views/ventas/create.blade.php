@extends('layouts.app')

@section('title', 'Nueva Venta')

@section('content')
    @php
        $productosData = $productos->mapWithKeys(fn ($producto) => [
            $producto->id => [
                'nombre' => $producto->nombre,
                'precio' => round((float) $producto->precio_unitario(), 2),
                'stock' => (int) ($producto->inventario?->disponible ?? 0),
            ],
        ]);
    @endphp

    <div class="max-w-6xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('ventas.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white">Nueva Venta</h1>
                <p class="text-white/50 text-sm">Venta por unidades · el stock se descuenta en unidades</p>
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
                        <label for="observaciones" class="block text-sm font-medium text-white/80 mb-2">Observaciones</label>
                        <textarea id="observaciones" name="observaciones" rows="2" class="input-glass"
                                  placeholder="Notas de la venta (opcional)">{{ old('observaciones') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="glass rounded-2xl p-6 mb-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-semibold text-white">Productos</h2>
                    <button type="button" id="btn-add-linea"
                            class="flex items-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-2 rounded-xl text-sm font-medium hover:bg-blue-500/30 transition-colors">
                        <i class="fas fa-plus"></i> Agregar línea
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="lineas-tabla">
                        <thead>
                            <tr class="text-left text-white/50 border-b border-white/10">
                                <th class="p-3 min-w-[240px]">Producto</th>
                                <th class="p-3 w-28">Unidades</th>
                                <th class="p-3 text-right w-28">Precio unit.</th>
                                <th class="p-3 w-32">Descuento</th>
                                <th class="p-3 text-right w-28">Total</th>
                                <th class="p-3 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="lineas-body"></tbody>
                    </table>
                </div>

                <p class="text-xs text-white/40 mt-3">
                    <i class="fas fa-circle-info mr-1"></i>
                    Cada unidad descuenta una unidad del inventario del producto. El precio unitario se calcula desde el precio del paquete ({{ \App\Models\ProductoTerminado::UNIDADES_POR_PAQUETE }} u).
                </p>
            </div>

            <div class="glass rounded-2xl p-6 mb-6">
                <h2 class="text-lg font-semibold text-white mb-5">Resumen y pago</h2>
                <div class="grid sm:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-white/60">Subtotal</span>
                            <span id="resumen-subtotal" class="text-white font-medium">$0</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-white/60">Descuento</span>
                            <span id="resumen-descuento" class="text-red-300 font-medium">$0</span>
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

    <template id="linea-template">
        <tr class="border-b border-white/5 align-top">
            <td class="p-3">
                <select name="lineas[__INDEX__][producto_terminado_id]" class="input-glass bg-gray-900/60 linea-producto" required>
                    <option value="">Seleccionar producto...</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}" data-precio="{{ round((float) $producto->precio_unitario(), 2) }}">
                            {{ $producto->nombre }}{{ $producto->presentacion ? ' ('.$producto->presentacion.')' : '' }}
                            — ${{ number_format($producto->precio_unitario(), 0, ',', '.') }}/u
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-white/40 mt-1 linea-info"></p>
            </td>
            <td class="p-3">
                <input type="number" name="lineas[__INDEX__][cantidad_unidades]" min="1" step="1" value="1"
                       class="input-glass text-right linea-cantidad" required>
            </td>
            <td class="p-3 text-right text-white/70 whitespace-nowrap linea-precio">—</td>
            <td class="p-3">
                <div class="flex gap-1">
                    <select name="lineas[__INDEX__][descuento_tipo]" class="input-glass bg-gray-900/60 w-16 linea-descuento-tipo">
                        <option value="monto">$</option>
                        <option value="porcentaje">%</option>
                    </select>
                    <input type="number" name="lineas[__INDEX__][descuento_valor]" min="0" step="0.01" value="0"
                           class="input-glass text-right linea-descuento-valor">
                </div>
            </td>
            <td class="p-3 text-right text-white font-medium whitespace-nowrap linea-total">$0</td>
            <td class="p-3 text-right">
                <button type="button"
                        class="linea-eliminar w-8 h-8 inline-flex items-center justify-center rounded-lg text-red-300 hover:bg-red-500/10 transition-colors"
                        title="Quitar línea">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    </template>
@endsection

@push('scripts')
    <script>
        window.__productos = @json($productosData);
        window.__lineasOld = @json(old('lineas', []));

        (function () {
            const productos = window.__productos || {};
            const template = document.getElementById('linea-template');
            const body = document.getElementById('lineas-body');
            const addBtn = document.getElementById('btn-add-linea');
            const formaPagoRadios = document.querySelectorAll('input[name="forma_pago"]');
            const abonoInput = document.getElementById('abono_inicial');
            const vencimientoInput = document.getElementById('fecha_vencimiento');
            const vencimientoContainer = document.getElementById('vencimiento-container');
            const vencimientoHint = document.getElementById('vencimiento-hint');
            const abonoContainer = document.getElementById('abono-container');
            const formaPagoAyuda = document.getElementById('forma-pago-ayuda');
            let index = 0;

            const money = new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                maximumFractionDigits: 0,
            });

            const fmt = (value) => money.format(isFinite(value) ? value : 0);

            // Manejar cambio de forma de pago
            formaPagoRadios.forEach(radio => {
                radio.addEventListener('change', () => {
                    if (!radio.checked) return;

                    if (radio.value === 'contado') {
                        // Modo contado: se cobra el total, sin vencimiento ni abono manual
                        abonoInput.value = '';
                        abonoInput.disabled = true;
                        abonoContainer.style.display = 'none';
                        vencimientoInput.value = '';
                        vencimientoInput.removeAttribute('required');
                        vencimientoContainer.style.display = 'none';
                        vencimientoHint.classList.add('hidden');
                        if (formaPagoAyuda) {
                            formaPagoAyuda.textContent = 'Contado: se cobra el 100% del total.';
                        }
                    } else {
                        // Modo crédito: abono opcional + vencimiento si queda saldo
                        abonoInput.disabled = false;
                        abonoContainer.style.display = '';
                        vencimientoContainer.style.display = '';
                        if (!vencimientoInput.value) {
                            const en30 = new Date();
                            en30.setDate(en30.getDate() + 30);
                            vencimientoInput.value = en30.toISOString().slice(0, 10);
                        }
                        if (formaPagoAyuda) {
                            formaPagoAyuda.textContent = 'Crédito: abono inicial opcional y saldo con vencimiento.';
                        }
                    }

                    recalcular();
                });
            });

            function addRow(data = {}) {
                const html = template.innerHTML.replaceAll('__INDEX__', index++);
                const wrapper = document.createElement('tbody');
                wrapper.innerHTML = html.trim();
                const row = wrapper.firstElementChild;
                body.appendChild(row);

                const select = row.querySelector('.linea-producto');

                if (data.producto_terminado_id) {
                    select.value = String(data.producto_terminado_id);
                }

                row.querySelector('.linea-cantidad').value = data.cantidad_unidades || 1;

                if (data.descuento_tipo) {
                    row.querySelector('.linea-descuento-tipo').value = data.descuento_tipo;
                }

                row.querySelector('.linea-descuento-valor').value = data.descuento_valor || 0;

                row.querySelectorAll('select, input').forEach((element) => {
                    element.addEventListener('input', () => recalcular());
                    element.addEventListener('change', () => recalcular());
                });

                row.querySelector('.linea-eliminar').addEventListener('click', () => {
                    row.remove();
                    recalcular();
                });

                calcularLinea(row);
            }

            function calcularLinea(row) {
                const select = row.querySelector('.linea-producto');
                const producto = productos[select.value];
                const cantidad = Math.max(parseInt(row.querySelector('.linea-cantidad').value || '0', 10), 0);
                const tipo = row.querySelector('.linea-descuento-tipo').value;
                const valor = Math.max(parseFloat(row.querySelector('.linea-descuento-valor').value || '0'), 0);

                const bruto = producto ? producto.precio * cantidad : 0;
                let descuento = 0;

                if (producto) {
                    descuento = tipo === 'porcentaje'
                        ? bruto * Math.min(valor, 100) / 100
                        : Math.min(valor, bruto);
                }

                const total = Math.max(bruto - descuento, 0);

                row.dataset.bruto = bruto;
                row.dataset.descuento = descuento;

                row.querySelector('.linea-precio').textContent = producto ? fmt(producto.precio) : '—';
                row.querySelector('.linea-total').textContent = fmt(total);

                const info = row.querySelector('.linea-info');

                if (producto) {
                    info.textContent = `${producto.stock} u disponibles · ${cantidad} u solicitadas`;
                    info.className = 'text-xs mt-1 linea-info ' + (cantidad > producto.stock ? 'text-red-400' : 'text-white/40');
                } else {
                    info.textContent = '';
                    info.className = 'text-xs mt-1 linea-info text-white/40';
                }

                return { bruto, descuento, total };
            }

            function recalcular() {
                let subtotal = 0;
                let descuento = 0;

                body.querySelectorAll('tr').forEach((row) => {
                    const valores = calcularLinea(row);
                    subtotal += valores.bruto;
                    descuento += valores.descuento;
                });

                const total = Math.max(subtotal - descuento, 0);
                
                // Determinar forma de pago actual
                const formaPagoSeleccionado = document.querySelector('input[name="forma_pago"]:checked');
                const esContado = formaPagoSeleccionado && formaPagoSeleccionado.value === 'contado';
                
                // Calcular abono
                let abono = 0;
                if (!esContado) {
                    abono = Math.max(parseFloat(abonoInput.value || '0'), 0);
                }
                const saldo = Math.max(total - abono, 0);

                document.getElementById('resumen-subtotal').textContent = fmt(subtotal);
                document.getElementById('resumen-descuento').textContent = fmt(descuento);
                document.getElementById('resumen-total').textContent = fmt(total);
                
                if (esContado) {
                    // Modo contado: abono = total automáticamente
                    abono = total;
                    document.getElementById('resumen-abono').textContent = fmt(total);
                    document.getElementById('resumen-saldo').textContent = fmt(0);
                    vencimientoHint.classList.add('hidden');
                    vencimientoInput.removeAttribute('required');
                } else {
                    // Modo crédito: mostrar abono y saldo
                    document.getElementById('resumen-abono').textContent = fmt(abono);
                    document.getElementById('resumen-saldo').textContent = fmt(saldo);
                    vencimientoHint.classList.toggle('hidden', saldo <= 0);
                    
                    if (saldo > 0) {
                        vencimientoInput.setAttribute('required', 'required');
                    } else {
                        vencimientoInput.removeAttribute('required');
                    }
                }
            }

            addBtn.addEventListener('click', () => {
                addRow();
                recalcular();
            });

            abonoInput.addEventListener('input', recalcular);

            // Crear las líneas ANTES de inicializar la forma de pago,
            // para que los cálculos encuentren las filas en el DOM.
            const oldLineas = window.__lineasOld || [];

            if (oldLineas.length) {
                oldLineas.forEach((linea) => addRow(linea));
            } else {
                addRow();
            }

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