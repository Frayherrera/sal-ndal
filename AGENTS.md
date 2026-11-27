# sal-ndal - Sistema de gestión para Santini

## Proyecto

Laravel 12 + Blade + Tailwind CSS v4 PWA project. Spanish language throughout.

**Domain:** Santini — comercializadora de condimentos y especias.

**Business model (inventory flow):**
1. **Purchase** raw materials (spices/condiments) by **kilograms**
2. **Store** the raw materials
3. **Prepare/pack** into commercial presentations (bags or 30 g, 50 g, 100 g jars)
4. **Save** as finished product
5. **Sell/distribute** to stores, supermarkets, restaurants, wholesalers and other clients

**Database name:** `santini_db` (MySQL on AWS RDS, not local)

## Dev Commands

```bash
# Full setup (installs deps, generates key, runs migrations, builds assets)
composer setup

# Dev server (runs artisan serve + queue + pail + vite concurrently)
composer dev

# Run tests (clears config first, uses SQLite in-memory)
composer test

# Or directly:
php artisan test
npx vite build
```

**Port:** `localhost:8080` (set in `.env` and `docker-compose.yml`)

## Docker

Single container (`php:8.2-apache`), no local MySQL. DB is remote AWS RDS.

```bash
docker compose up --build
```

## Laravel 12 Notes

- No `App\Http Kernel` class. Middleware/routes configured in `bootstrap/app.php`.
- Only `routes/web.php` registered. No `routes/api.php`.

## Tailwind CSS v4

Uses new `@tailwindcss/vite` plugin. CSS entry `resources/css/app.css` uses v4 syntax:
- `@import 'tailwindcss'` (not `@tailwind` directives)
- `@source` for content paths
- `@theme` for design tokens

**Note:** `welcome.blade.php` also loads Tailwind via CDN `<script>` — mixed approach, may cause conflicts.

## PWA Setup

- Manifest: `public/manifest.json` (name "Santini", incluye `id`, `scope`, `start_url` `/`, `display_override`; iconos android/ios/windows11 + maskable apuntando a archivos reales)
- Service worker: `public/sw.js` (network-first para navegación con fallback a cache, cache-first para assets; precachea manifest + favicon + iconos clave)
- Icons: `public/pwa/icons/` — android (nombres `launchericon-48x48.png` con `x`), ios, windows11 (el dir en disco es `windows11`, no `windows`)
- SW registrado en `resources/js/app.js`

**Ojo:** el manifest se genera a partir de los PNG reales (`public/pwa/icons`). Si regeneras los iconos, valida que `manifest.json` solo referencie archivos que existen (verificado: 112 rutas, 0 faltantes).

## Módulo Inventario

Arquitectura **Controller → Service → Model** (sin Livewire ni Alpine; JS plano).

**Tablas (migraciones `2026_08_30_*`):**
- `materias_primas` — CRUD con `unidad_base` (kg/g) y `stock_minimo`
- `producto_terminados` — CRUD con `presentacion`, `peso_neto`, `precio_venta`
- `inventario_materia_prima` — snapshot 1:1: `stock_gramos` (BIGINT, gramos), `costo_promedio`
- `inventario_producto_terminado` — snapshot 1:1: `disponible`, `comprometido` (unidades)
- `movimientos_inventario` — **kardex** (morph `origen`: MP/PT), `tipo`, `direccion`, `saldo`, `documento`, `movimiento_original_id`, `conteo_fisico_id`
- `conteos_fisicos` — `estado`: borrador → completado → aprobado / anulado
- `detalle_conteo_fisico` — líneas con `stock_sistema`, `cantidad_fisica`, `diferencia`, `motivo`
- `configuracion_inventario` — clave/valor (ej. `permitir_stock_negativo`)
- `detalle_receta` — **BOM/receta** de producción: `producto_terminado_id`, `materia_prima_id`, `gramos_por_unidad` (gramos de MP por 1 unidad de PT), unique `(producto_terminado_id, materia_prima_id)`

**Modelo de stock:** Las tablas `inventario_*` son snapshots de lectura rápida; el kardex (`movimientos_inventario`) es el log de auditoría. Las materias primas se almacenan en **gramos** internamente (los forms en kg/g según `unidad_base`).

**Tipos de movimiento:** `compra_recepcion`, `consumo_produccion`, `producto_producido`, `venta_despacho`, `devolucion`, `ajuste_positivo`, `ajuste_negativo`, `anulacion_reversion`.

**Producción con receta (BOM):** el módulo `ProduccionController` + `MovimientoInventarioService::producir()` registra en **una transacción** los movimientos `consumo_produccion` (salida, resta materia prima) + `producto_producido` (entrada, suma producto). Pre-valida stock de todas las materias primas y aborta (rollback) si alguna no alcanza. Las recetas se definen en el módulo `RecetaController`/`RecetaService` (modo `/inventario/recetas`). El formulario de producción captura **paquetes + unidades sueltas** (`paquetes`, `sueltas`) y el controlador los convierte a unidades con `Paquetes::aUnidades()`.

**Servicios clave:** `app/Services/MovimientoInventarioService.php` (motor de stock, `producir()`, transacciones, validación de stock negativo, anulaciones por reversión), `RecetaService.php` (CRUD de recetas/BOM), `ConteoFisicoService.php` (aprobación genera ajustes), `InventarioService.php` (dashboard + alertas).

**Conteo físico (`/inventario/conteo-fisico`):** borrador → completado → aprobado (o anulado desde borrador/completado).
- Solo un **borrador** admite cambios de líneas; completado queda fijo para revisión (si hay error, se anula y se crea otro).
- `ConteoFisicoService::registrarDetalle()` reemplaza las líneas: una por artículo (rechaza duplicados, líneas sin artículo o sin cantidad). El **`stock_sistema` lo toma el servidor** del inventario al guardar (lo que envíe el cliente se ignora) y `diferencia = cantidad_fisica − stock_sistema`.
- Al aprobar se genera un `ajuste_positivo`/`ajuste_negativo` por la **diferencia** (no se fuerza el stock al valor contado), así que los movimientos posteriores al conteo se conservan.
- El formulario captura la materia prima en su `unidad_base` (kg o g) y el controlador la convierte a gramos; producto terminado en unidades. Los campos van **con índice por fila** (`detalles[i][campo]`): con `detalles[][campo]` PHP separa cada campo en una línea distinta. Las filas vacías se ignoran.

**Notas de implementación:**
- No se borran movimientos: se anulan creando una reversión espejo.
- Stock negativo prohibido por defecto (configurable vía `permitir_stock_negativo`).
- `/gestion` apunta a `InventarioController@dashboard`.
- Vistas en `resources/views/inventario/**` usando el layout compartido `layouts/app.blade.php`.
- El input de cantidad (`movimientos/create`) usa `min="0"` + `step` dinámico (0.001 para MP en kg, 1 para PT) para evitar el error de validación HTML de step (p.ej. exigir 5.001 al escribir 5).

## Módulo Ventas

Venta **por valor total** (sin productos) con contado/crédito, abonos, cartera y reportes. Misma arquitectura Controller → Service → Model, JS plano.

**Cambio a valor total (nov 2026):** la venta ya no lista productos ni descuenta inventario. Se registra cliente + `total` digitado + forma de pago; la descripción va en `observaciones`. No hay descuentos. Las ventas antiguas (por productos/unidades) conservan su `detalle_venta` y sus movimientos `venta_despacho`, y `show`/`recibo` las siguen mostrando con su tabla de productos.

**Tablas (migraciones `2026_11_09_*`):**
- `ventas` — maestro: `numero` (`VTA-00001`, único), `cliente_id`, `user_id` (vendedor), `fecha`, `subtotal`, `descuento_total`, `total`, `estado` (`confirmada` | `anulada`), `forma_pago` (`contado` | `credito`), `fecha_vencimiento`, `observaciones`, datos de anulación (`anulada_at`, `anulada_por`, `motivo_anulacion`). En ventas nuevas `subtotal = total` y `descuento_total = 0`.
- `detalle_venta` — **solo histórico** (ventas por producto anteriores al cambio): `precio_unidad`, `cantidad_unidades`, descuentos por línea, `subtotal`, `total`. Las ventas nuevas no crean filas aquí.
- `pago_venta` — abonos: `fecha`, `monto`, `metodo` (`efectivo`, `transferencia`, `nequi`, `daviplata`, `otro`), `referencia`, `nota`, `user_id`. El abono inicial se guarda aquí con nota "Abono inicial".
- `movimientos_inventario.venta_id` — traza los `venta_despacho` de ventas antiguas; se revierten al anularlas.

**Reglas de negocio:**
- `total` obligatorio y mayor a cero (validado en `VentaController::validar()` y en `VentaService::crear()`).
- **Forma de pago switch:** en el formulario hay un interruptor para seleccionar **Contado** o **Crédito**:
  - **Contado**: el abono inicial cubre el 100% del total automáticamente; no hay fecha de vencimiento; la venta queda "pagada" al instante. En el formulario el campo de abono y el de vencimiento se ocultan.
  - **Crédito**: se ingresa un abono inicial (puede ser 0 o parcial); si queda saldo, la fecha de vencimiento es obligatoria (por defecto +30 días); queda un saldo pendiente.
- La forma de pago se **guarda** en `ventas.forma_pago` (`Venta::FORMA_CONTADO` / `Venta::FORMA_CREDITO`) y `Venta::formaPago()` devuelve la etiqueta. No se deriva del saldo: una venta a crédito ya pagada sigue mostrándose como "Crédito".
- Cliente obligatorio, vendedor = usuario autenticado, consecutivo `VTA-00001`, COP, sin IVA ni datos fiscales.
- Estados de pago derivados (no se guardan): `pagada` / `pendiente` / `vencida` / `anulada`, calculados con `totalPagado()` / `saldo()` (`withSum('pagos','monto')` cuando está disponible).
- Anulación: solo ventas `confirmada` y **sin abonos**; marca la venta como anulada (no se borra). Si es una venta antigua con movimientos de inventario, los revierte con `MovimientoInventarioService::anular()` (espejo `anulacion_reversion`).

**Rutas (`/ventas`):** `index`, `crear`, `store`, `{venta}`, `{venta}/recibo` (vista imprimible), `{venta}/anular` (GET/POST), `{venta}/abonos/crear` + `{venta}/abonos`, `cartera`, `reportes/clientes`, `exportar` (CSV). Las rutas estáticas van antes de `{venta}` y éstas usan `whereNumber`. El reporte `reportes/productos` se eliminó con el cambio a valor total.
**Reporte en PDF (`/ventas/exportar-pdf`, botón «PDF» del listado):** usa `barryvdh/laravel-dompdf` y los mismos filtros del listado (`consultaFiltrada()`). `ReporteVentasService::generar()` arma los KPIs (solo ventas confirmadas: total, cobrado, saldo, ticket promedio, contado vs. crédito, vencidas, anuladas excluidas), ventas por día, top 10 clientes con participación y saldo, y el detalle (máximo `MAX_DETALLE` = 1.000 filas). Vista `resources/views/ventas/reportes/pdf.blade.php` sobre el diseño común de reportes PDF (ver «Reportes PDF»).

**Servicios clave:** `VentaService` (crear por valor total con switch contado/crédito, anular), `PagoVentaService` (registrar abono, valida saldo y método).

**Vistas:** `resources/views/ventas/{index,create,show,recibo,anular,cartera,abonos/create,reportes/clientes}.blade.php`. El formulario de creación tiene un campo de valor total, el switch `Contado/Crédito` y JS plano que recalcula abonado/saldo en vivo. `show` y `recibo` muestran "Venta por valor total" + observaciones, o la tabla de productos si la venta es antigua.

**Dashboard:** `InventarioService::resumen()` agrega `ventas_hoy_*`, `ventas_mes_*`, `cartera_pendiente` y `cartera_vencidas`; el dashboard muestra esas tarjetas y la acción rápida "Registrar Venta" apunta a `ventas.create`.

**Menú:** ítems "Ventas" (`fa-cash-register`) y "Cartera" (`fa-wallet`) en `layouts/app.blade.php`.

## Módulo Gastos

Registro de gastos operativos con categorías editables, más un **gasto automático por cada compra de materia prima con costo**. Misma arquitectura Controller → Service → Model, JS plano.

**Tablas (migraciones `2026_11_12_*`):**
- `categorias_gasto` — `nombre` (único), `descripcion`, `clave` (única, nullable), `activo`. La migración **inserta las categorías iniciales** (Arriendo, Servicios públicos, Nómina, etc.) para que existan también en producción. La categoría con `clave = compra_materia_prima` es del sistema: no se elimina, no se desactiva y no aparece en el formulario de gastos manuales.
- `gastos` — `numero` (`GTO-00001`), `fecha` (date), `categoria_gasto_id`, `descripcion`, `monto`, `metodo_pago` (nullable, mismos valores que `PagoVenta::METODOS`), `proveedor`, `estado` (`registrado` | `anulado`), `movimiento_inventario_id` (único, nullable: gasto automático), `user_id`, datos de anulación (`anulado_at`, `anulado_por`, `motivo_anulacion`).

**Reglas de negocio:**
- **Gasto automático:** `MovimientoInventarioService::registrar()` llama a `GastoService::registrarDesdeCompra()` dentro de la misma transacción cuando el tipo es `compra_recepcion` y `costo_total > 0`. Toma el proveedor de la materia prima y arma la descripción con nombre, cantidad (kg) y documento.
- En el formulario de movimientos el **costo total es obligatorio para compras** (`required_if:tipo,compra_recepcion`). A nivel de servicio sigue siendo opcional (sin costo no se crea gasto).
- `MovimientoInventarioService::anular()` anula también el gasto de esa compra (`GastoService::anularPorMovimiento()`).
- Los gastos automáticos **no se editan ni se anulan desde Gastos**: se corrigen anulando la compra en Movimientos.
- Los gastos manuales se editan y se anulan (con motivo); nunca se borran. Las categorías con gastos no se eliminan, solo se desactivan.
- Los totales, reportes y el dashboard solo suman gastos `registrado`.

**Rutas (`/gastos`):** `index`, `crear`, `store`, `exportar` (CSV), `reportes/categorias`, `reportes/resultado`, `categorias` (resource sin `show`), `{gasto}/editar`, `{gasto}` (PUT), `{gasto}/anular` (GET/POST). Rutas estáticas antes de `{gasto}`, que usa `whereNumber`.

**Reportes:** `ReporteGastoController::categorias()` (totales y % por categoría, por defecto el mes actual) y `resultado()` (por mes de un año: vendido, cobrado, gastado y ventas − gastos; se agrupa en PHP para no depender del motor de BD).
**Reporte en PDF (`/gastos/exportar-pdf`, botón «PDF» del listado):** mismos filtros del listado. `ReporteGastosService::generar()` arma los KPIs (solo gastos registrados: total, operativos vs. compras de materia prima automáticas, promedio, mayor gasto, anulados excluidos), gastos por categoría con participación, por día, principales proveedores (agrupados sin distinguir mayúsculas; la sección se omite si ningún gasto tiene proveedor) y el detalle (máximo 1.000 filas). Vista `resources/views/gastos/reportes/pdf.blade.php`.

**Servicios clave:** `GastoService` (crear, actualizar, anular, gasto automático de compras), `CategoriaGastoService` (CRUD con protección de la categoría del sistema).

**Vistas:** `resources/views/gastos/{index,create,edit,_form,anular,categorias/{index,create,edit,_form},reportes/{categorias,resultado}}.blade.php`. El detalle de un movimiento muestra el gasto que generó (`MovimientoInventario::gasto()`).

**Dashboard:** `InventarioService::resumen()` agrega `gastos_hoy_*`, `gastos_mes_*` y `resultado_mes` (ventas − gastos del mes); el dashboard muestra esas tarjetas y la acción rápida "Registrar Gasto".

**Menú:** ítem "Gastos" (`fa-receipt`) en `layouts/app.blade.php`.

## Bot de Telegram

Bot para **consultar** y **registrar** desde Telegram. Recibe los mensajes por **webhook** (`POST /telegram/webhook`, sin sesión ni CSRF) y solo atiende los **chats autorizados**.

**Configuración (`.env`, leída en `config/services.php` → `telegram`):**
- `TELEGRAM_BOT_TOKEN` (de @BotFather), `TELEGRAM_WEBHOOK_SECRET` (texto aleatorio; Telegram lo envía en la cabecera `X-Telegram-Bot-Api-Secret-Token` y el webhook responde 403 si no coincide o si no está definido), `TELEGRAM_ALLOWED_CHAT_IDS` (IDs separados por coma), `TELEGRAM_USER_EMAIL` (usuario a cuyo nombre quedan los registros; por defecto `ADMIN_EMAIL`).
- Un chat no autorizado recibe su propio ID para poder agregarlo.
- Webhook: `php artisan telegram:webhook set [--url=https://...]` (exige HTTPS; usa `APP_URL` si no se pasa `--url`; también publica el menú de comandos), `telegram:webhook info`, `telegram:webhook delete`. En local hace falta un túnel HTTPS (p. ej. `cloudflared tunnel --url http://localhost:8080`).

**Código (`app/Services/Telegram/`):**
- `TelegramClient` — llamadas a la Bot API con `Http` (en tests: `Http::fake()`). No propaga la URL en errores de conexión porque incluye el token.
- `TelegramBot` — procesa updates: enruta comandos (acepta `/cmd@NombreBot`), autoriza el chat y maneja los botones de confirmación. `TelegramBot::COMANDOS` es el menú publicado.
- `TelegramConsultas` — `/resumen` (usa `InventarioService::resumen()`), `/cartera [cliente]`, `/stock texto`, `/alertas`, `/ventas [n]`, `/gastos [n]`.
- `TelegramCamion` — consultas del módulo Camión (solo lectura, usa `ViajeCamionService`): `/camion` (en ruta o en casa y qué lleva), `/vendido [viaje]` (lo vendido por producto en el último viaje cerrado o en `VJE-0000N`, con merma, ventas registradas, gastos de ruta y resultado), `/semanas [producto]` (vendido por viaje; con producto, ese producto viaje por viaje). También `/rutas` (rutas en orden de rotación). El asistente con IA las expone como `estado_camion`, `vendido_viaje`, `vendido_por_semana` y `listar_rutas`. El cargue y el regreso no se hacen por el bot.
- `TelegramRegistros` — `/gasto valor descripción [| categoría]` (categoría por defecto «Otros»), `/venta cliente valor [contado|credito] [| nota]` (crédito: vence a 30 días, sin abono), `/abono cliente valor [método]` (se reparte entre las ventas con saldo más antiguas). Usa `GastoService`, `VentaService` y `PagoVentaService`. Además `/ruta nombre [| ciudad, ciudad…]` (`prepararRuta`/`prepararRutaDatos`, función de IA `preparar_ruta`): crea una ruta del camión al final de la rotación, rechaza nombres repetidos sin distinguir mayúsculas ni tildes y empareja las ciudades con la escritura de `clientes.ciudad`; también pide confirmación.
- `BuscadorTelegram` — encuentra cliente (por nombre o local) y categoría sin distinguir mayúsculas, tildes ni espacios: exacto → empieza por → contiene; si hay varios, pide precisar.
- `TelegramFormato` — dinero, escape HTML, normalización y `monto()` (acepta 70000, 70.000, 70,000, $70.000, 70k, 1,5m).

**Flujo de los registros:** `preparar*()` interpreta el texto y **no guarda nada**; el bot muestra el resumen con botones ✅/❌ y guarda la acción en caché 15 minutos (`telegram:confirmacion:{token}`, `CACHE_STORE=database`). Al confirmar, `ejecutar()` guarda con los servicios de la app, que **revalidan** (el saldo pudo cambiar). El botón solo sirve una vez y solo desde el mismo chat. El webhook responde siempre 200 a Telegram (si no, reintenta el update) y registra los errores en el log.

**Asistente con IA (Gemini, `app/Services/Telegram/IA/`):** los mensajes **sin `/`** van a `AsistenteTelegram`; los comandos con `/` no pasan por la IA.
- Configuración: `GEMINI_API_KEY` (capa gratuita de Google AI Studio) y `GEMINI_MODELOS` (lista en orden; por defecto `gemini-3.1-flash-lite,gemini-3.5-flash-lite,gemini-3.5-flash`). Sin key, el texto libre responde con la sugerencia de `/ayuda`.
- `GeminiModeloIA` llama a `generateContent` con function calling (key en la cabecera `x-goog-api-key`, nunca en la URL). Si un modelo responde 503/404/500 prueba el siguiente; un 429 lanza `LimiteIAException` y el bot avisa que se acabó el cupo gratuito. Devuelve el turno del modelo sin cambios para reenviarlo (conserva las firmas de razonamiento; los `args` vacíos se reenvían como `{}`).
- `AsistenteTelegram` declara funciones que llaman a `TelegramConsultas` y a `TelegramRegistros::preparar*Datos()`. Las funciones `preparar_*` **solo preparan**: el bot muestra el resumen con ✅/❌ (mismo flujo de confirmación) y nada se guarda sin confirmar. Los errores de validación vuelven al modelo como `error` para que los explique. Recuerda los últimos 6 intercambios por chat (caché 60 min) como texto de contexto. Máximo 6 vueltas por mensaje.
- `ModeloIA` es la interfaz (enlazada a `GeminiModeloIA` en `AppServiceProvider`); los tests usan un modelo simulado y `phpunit.xml` fuerza `GEMINI_API_KEY` vacía para no llamar nunca a la API real.
- El webhook deduplica por `update_id` (caché 1 h) porque Telegram reintenta si la respuesta tarda.

## Módulo Camión (cargue y descargue)

Un solo camión hace **un viaje por semana** a una **ruta fija** (las rutas rotan en orden). El módulo registra qué sale y qué regresa para saber **qué se vende por semana por producto**, ya que las ventas se registran por valor total y no por producto.

**Tablas (migraciones `2026_11_22_*`):**
- `rutas` — `nombre` (único), `orden` (posición en la rotación), `ciudades` (JSON, coinciden con `clientes.ciudad`), `descripcion`, `activo`.
- `viajes_camion` — `numero` (`VJE-00001`), `ruta_id`, `fecha_salida`, `fecha_regreso`, `estado` (`en_ruta` | `cerrado` | `anulado`), `observaciones`, `user_id`, cierre (`cerrado_at`, `cerrado_por`) y anulación (`anulado_at`, `motivo_anulacion`).
- `detalle_viaje_camion` — por producto, todo en **unidades**: `inicial` (venía del viaje anterior), `surtido` (desde bodega), `bajado` (a bodega), `cargado` (= inicial + surtido − bajado), `regresado`, `danado`, `regalado`, `vendido` (= cargado − regresado − dañado − regalado), `precio_unidad` (snapshot al cargar). Único `(viaje, producto)`.
- `movimientos_inventario.viaje_camion_id` — traza los movimientos del viaje.

**Reglas de negocio (`ViajeCamionService`):**
- El camión es una **segunda ubicación** del producto terminado. Surtir genera `cargue_camion` (salida de bodega) y bajar genera `descargue_camion` (entrada a bodega), con `documento` = número del viaje. El **cierre no mueve bodega**: lo vendido, dañado y regalado ya había salido al surtir.
- Solo un viaje `en_ruta` a la vez. El stock del camión es el `cargado` del viaje en ruta o, si está en casa, el `regresado` del último viaje cerrado; ese es el `inicial` del siguiente cargue.
- `rutaSugerida()`: la ruta activa que sigue en `orden` después del último viaje no anulado; después de la última vuelve a la primera.
- `promedioVendido()`: promedio de los últimos 3 viajes cerrados de la ruta; el formulario de cargue lo usa para sugerir el surtido.
- Validaciones: no se baja más de lo que hay en el camión, el camión no sale vacío, el surtido respeta el stock de bodega (lo valida `MovimientoInventarioService`) y al cerrar lo anotado no puede superar lo que salió.
- `anular()`: solo viajes en ruta; revierte el surtido y lo bajado con `anulacion_reversion`.
- `resultado()`: vendido a precio de catálogo frente a las ventas confirmadas entre la salida y el regreso a clientes de las ciudades de la ruta (todas las ventas si la ruta no tiene ciudades), menos los gastos de la categoría «Ruta» en esas fechas.
- Los tipos `cargue_camion` y `descargue_camion` están en `MovimientoInventario::TIPOS_AUTOMATICOS`: no aparecen ni se aceptan en el formulario manual de Movimientos.

**Captura en paquetes:** los formularios piden **paquetes + unidades sueltas**; `App\Support\Paquetes` convierte (`aUnidades`, `separar`) y formatea (`texto(245)` → «12 p + 5 u»).

**Rutas (`/camion`):** `index`, `cargar` (GET) + `store`, `reportes/semanal`, `rutas` (resource sin `show`), `{viaje}`, `{viaje}/cerrar` (GET/POST), `{viaje}/anular` (POST). Rutas estáticas antes de `{viaje}`, que usa `whereNumber`.

**Vistas:** `resources/views/camion/{index,create,cerrar,show,rutas/{index,create,edit,_form},reportes/semanal}.blade.php`. El reporte semanal es una matriz producto × viaje cerrado con totales en paquetes y pesos, y la merma por viaje. Menú: «Camión» (`fa-truck`).

## Reportes PDF

Diseño común para los reportes descargables (hoy ventas y gastos), con `barryvdh/laravel-dompdf`:
- `App\Support\PdfReporte::descargar(vista, datos, titulo, prefijoArchivo, filtros)`: carga la vista, agrega logo (`public/logo.jpeg` en base64), periodo, usuario y fecha, activa el *subsetting* de fuentes (sin él cada PDF pesa ~1,2 MB por las fuentes DejaVu), dibuja el pie «Santini · título» y «Página X de Y» con el canvas de dompdf y devuelve la descarga `prefijo-AAAA-MM-DD_a_AAAA-MM-DD.pdf`.
- `resources/views/reportes/pdf/layout.blade.php`: estilos y encabezado; cada reporte extiende el layout y llena `@section('contenido')`. CSS compatible con dompdf: tablas, sin flexbox ni JS; `.bloque` evita partir tablas cortas entre páginas; el `thead` de las tablas largas se repite en cada página.
- `App\Support\AgrupaPorPeriodo` (trait): periodo del reporte (filtros o fechas de los registros) y agrupación para el gráfico de barras: hasta 14 días incluye los días vacíos, de 15 a 31 solo los días con movimiento y más de 31 agrupa por semana.
- Reportes de **inventario actual** (sin fechas): `/inventario/materia-prima/reporte-pdf` y `/inventario/productos-terminados/reporte-pdf` (botón «PDF» de cada listado; rutas declaradas antes de los `resource` para que `{id}` no las capture). `ReporteInventarioService::materiaPrima()` / `productoTerminado()` incluyen los activos y los inactivos que aún tienen stock, agrupados por categoría («Sin categoría» al final) con subtotales; estado `agotado` / `bajo` (tiene mínimo y está en o por debajo) / `ok`. Producto terminado suma **bodega + camión** (`ViajeCamionService::stockCamion()`) y su estado mira la bodega; se muestra en paquetes con `Paquetes::texto()`. Pasan `corte` en vez de `desde`/`hasta`: `PdfReporte` muestra «Fecha de corte» y nombra el archivo `prefijo-AAAA-MM-DD.pdf`. Las vistas ocultan columnas vacías (proveedor/ubicación) y el encabezado de grupo si todo es «Sin categoría». Parcial común `reportes/pdf/_estados` (barra OK / bajo mínimo / agotados). La materia prima no se valoriza porque `costo_promedio` no se calcula hoy.

## Known Issues

1. ~~**Dual JS loading:** cargaban JS vía `@vite()` y `asset('js/app.js')` — el service worker se registraba dos veces.~~ Corregido: se usa solo `@vite(['resources/js/app.js'])`.
2. ~~**Missing maskable icons:** los iconos maskable apuntaban a `maskable-icons/` inexistente.~~ Corregido: el manifest apunta a archivos android reales con `purpose: maskable`.
3. **Project name inconsistency:** `.env` dice "Laravel", HTML dice "TuProyecto".
4. **`gestion.blade.php` huérfano:** la vista standalone quedó sin uso tras apuntar `/gestion` al dashboard (se mantiene como referencia de diseño).
5. **`public/build` desactualizado:** el bundle de Vite en `public/build/manifest.json` es viejo; `@vite()` sirve JS/CSS de hace varios commits. Rebuildear con `npm run build` (Node no está en el contenedor PHP — correr en el host).

## Testing

- PHPUnit 11.5
- Tests use **SQLite in-memory** (`phpunit.xml` overrides DB config)

> **CRÍTICO — los tests nunca deben tocar la BD real.** El contenedor Docker define
> `DB_CONNECTION=mysql` y `DB_DATABASE=santini_db` como variables de entorno. Laravel lee
> `$_SERVER` antes que `.env`, y por defecto PHPUnit **no sobrescribe** variables ya definidas,
> así que los tests terminaban corriendo sobre MySQL y `RefreshDatabase` borraba la base de
> desarrollo. Por eso `phpunit.xml` declara los `env` con `force="true"` **y** los `<server>`
> equivalentes, y `tests/TestCase.php` aborta si la conexión no es `sqlite`.
> Si alguna vez los tests vuelven a borrar datos, revisa primero esos dos archivos.
- Tests del módulo inventario:
  - `tests/Unit/MovimientoInventarioServiceTest.php` — motor de stock, reversiones, stock negativo
  - `tests/Unit/ConteoFisicoServiceTest.php` — flujo de conteo, stock del sistema calculado en servidor, validación de líneas, ajustes por diferencia
  - `tests/Feature/ConteoFisicoFeatureTest.php` — conteo vía formulario (campos `detalles[i][...]`, kg → g, filas vacías, completado no editable)
  - `tests/Feature/MateriaPrimaFeatureTest.php`, `ProductoTerminadoFeatureTest.php`, `MovimientoInventarioFeatureTest.php`
  - `tests/Feature/RecetaFeatureTest.php` — CRUD de recetas/BOM
  - `tests/Feature/ProduccionFeatureTest.php` — producción descuenta MP y suma PT, rechaza sin receta / con stock insuficiente
  - `tests/Feature/ClienteFeatureTest.php` — CRUD de clientes
  - `tests/Unit/VentaServiceTest.php` — venta por valor total, contado/crédito, abonos, anulación (incluye reversión de stock de ventas antiguas)
  - `tests/Feature/VentaFeatureTest.php` — listado, formulario, store, recibo, anulación y CSV
  - `tests/Feature/ReporteVentaFeatureTest.php` — reporte por cliente y filtros de fecha
  - `tests/Feature/ReporteVentasPdfTest.php` — reporte de ventas en PDF: KPIs, agrupación por día/semana, top clientes, filtros y descarga del PDF
  - `tests/Feature/ReporteGastosPdfTest.php` — reporte de gastos en PDF: KPIs con compras de materia prima, categorías, días, proveedores, filtros y descarga
  - `tests/Feature/ReporteInventarioPdfTest.php` — inventario actual en PDF: estados, categorías, inactivos con stock, bodega + camión, descarga con fecha de corte y que la ruta no choque con el detalle
  - `tests/Unit/GastoServiceTest.php` — gastos manuales, gasto automático de compras, anulación en cascada, protección de categorías
  - `tests/Feature/GastoFeatureTest.php` — listado, filtros, CRUD, costo obligatorio en compras, CSV, reportes, dashboard y categorías
  - `tests/Feature/TelegramCamionTest.php` — consultas del camión en el bot (`/camion`, `/vendido`, `/semanas`) y funciones del asistente con IA
  - `tests/Feature/TelegramBotTest.php` — bot de Telegram: secreto del webhook, chats autorizados, consultas, registros con confirmación, reparto de abonos, montos y comando `telegram:webhook`
  - `tests/Feature/TelegramAsistenteIATest.php` — asistente con IA (modelo simulado): consultas, registros con confirmación, errores, memoria, límite gratuito, deduplicación y cliente de Gemini
  - `tests/Unit/ViajeCamionServiceTest.php` — camión: paquetes ↔ unidades, cargue descuenta bodega, cierre calcula vendido, rotación de rutas, bajar a bodega, validaciones, anulación, promedio y resultado del viaje
  - `tests/Feature/CamionFeatureTest.php` — camión vía pantallas: rutas, flujo semanal completo, errores, anulación y tipos ocultos en Movimientos
  - `tests/Feature/ViewsSmokeTest.php` — smoke test de render de todas las vistas (incluyendo recetas, producción, clientes, ventas y gastos)

**Importante:** El caché de rutas/config (`bootstrap/cache/routes-v7.php`, `config.php`) se regenera apuntando a MySQL RDS y predata a los cambios. Antes de correr tests/artisan localmente conviene limpiarlo (`php artisan config:clear` + `php artisan route:clear`) — el script `composer test` ya hace `config:clear`.

## Conventions

- Comments and UI text in Spanish
- 4-space indent (`.editorconfig`)
- LF line endings

## Design System

**Todas las vistas deben seguir este estilo visual consistente:**

- **Fondo:** Gradiente oscuro `bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900`
- **Elementos:** Glass morphism con `background: rgba(255,255,255,0.08)`, `backdrop-filter: blur(12px)`, `border: 1px solid rgba(255,255,255,0.15)`
- **Acentos:** Azul (`blue-500`) y morado (`purple-600`) en gradientes
- **Iconos:** Font Awesome 6
- **Tipografía:** Figtree (vía fonts.bunny.net)
- **Componentes:** Bordes redondeados (`rounded-xl`, `rounded-2xl`), sombras con color (`shadow-blue-500/30`)
- **Animaciones:** `fadeInUp` para entrada de elementos
- **Tailwind:** Carga vía CDN (`<script src="https://cdn.tailwindcss.com">`)

**Archivos de referencia:** `welcome.blade.php`, `auth/login.blade.php`

## Design Skills

Cuando el usuario pida algo de diseño UI, diseño visual, estética, tipografía, layout, colores, o cualquier trabajo de frontend, **siempre usar la skill `frontend-design`**. Cargarla antes de responder.
