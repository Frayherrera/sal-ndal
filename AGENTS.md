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

**Producción con receta (BOM):** el módulo `ProduccionController` + `MovimientoInventarioService::producir()` registra en **una transacción** los movimientos `consumo_produccion` (salida, resta materia prima) + `producto_producido` (entrada, suma producto). Pre-valida stock de todas las materias primas y aborta (rollback) si alguna no alcanza. Las recetas se definen en el módulo `RecetaController`/`RecetaService` (modo `/inventario/recetas`).

**Servicios clave:** `app/Services/MovimientoInventarioService.php` (motor de stock, `producir()`, transacciones, validación de stock negativo, anulaciones por reversión), `RecetaService.php` (CRUD de recetas/BOM), `ConteoFisicoService.php` (aprobación genera ajustes), `InventarioService.php` (dashboard + alertas).

**Notas de implementación:**
- No se borran movimientos: se anulan creando una reversión espejo.
- Stock negativo prohibido por defecto (configurable vía `permitir_stock_negativo`).
- `/gestion` apunta a `InventarioController@dashboard`.
- Vistas en `resources/views/inventario/**` usando el layout compartido `layouts/app.blade.php`.
- El input de cantidad (`movimientos/create`) usa `min="0"` + `step` dinámico (0.001 para MP en kg, 1 para PT) para evitar el error de validación HTML de step (p.ej. exigir 5.001 al escribir 5).

## Módulo Ventas

Venta de producto terminado **por unidad** con contado/ crédITO, abonos, cartera y reportes. Misma arquitectura Controller → Service → Model, JS plano.

**Cambio a unidades:** El catálogo conserva su `precio_venta` por paquete (20 u), así que el precio unitario se deriva como `precio_venta ÷ 20` y el stock se descuenta en unidades. El modelo `UNIDADES_POR_PAQUETE = 20` se conserva en el catálogo.

**Tablas (migraciones `2026_11_09_*`):**
- `ventas` — maestro: `numero` (`VTA-00001`, único), `cliente_id`, `user_id` (vendedor), `fecha`, `subtotal`, `descuento_total`, `total`, `estado` (`confirmada` | `anulada`), `forma_pago` (`contado` | `credito`), `fecha_vencimiento`, `observaciones`, datos de anulación (`anulada_at`, `anulada_por`, `motivo_anulacion`).
- `detalle_venta` — líneas con snapshot de `precio_unidad` (precio unitario, congelado al momento de la venta), `cantidad_unidades`, `descuento_tipo` (`porcentaje` | `monto`), `descuento_valor`, `descuento_monto`, `subtotal` (bruto) y `total` (neto). Se eliminaron `cantidad_paquetes`, `precio_paquete` y `unidades_por_paquete`.
- `pago_venta` — abonos: `fecha`, `monto`, `metodo` (`efectivo`, `transferencia`, `nequi`, `daviplata`, `otro`), `referencia`, `nota`, `user_id`. El abono inicial se guarda aquí con nota "Abono inicial".
- `movimientos_inventario.venta_id` — traza los `venta_despacho` de una venta y permite revertirlos al anular.

**Reglas de negocio:**
- Precio unitario = `round(precio_venta / 20, 2)` vía `ProductoTerminado::precio_unitario()`; el catálogo y su seeder **no cambian** (siguen con precio por paquete).
- `ProductoTerminado::UNIDADES_POR_PAQUETE = 20` se conserva (catálogo/seeder/precio unitario).
- El stock se descuenta **al confirmar**: `cantidad_unidades` unidades, movimiento `venta_despacho`.
- **Forma de pago switch:** en el formulario hay un interruptor para seleccionar **Contado** o **Crédito**:
  - **Contado**: el abono inicial cubre el 100% del total automáticamente; no hay fecha de vencimiento; la venta queda "pagada" al instante. En el formulario el campo de abono y el de vencimiento se ocultan.
  - **Crédito**: se ingresa un abono inicial (puede ser 0 o parcial); si queda saldo, la fecha de vencimiento es obligatoria (por defecto +30 días); queda un saldo pendiente.
- La forma de pago se **guarda** en `ventas.forma_pago` (`Venta::FORMA_CONTADO` / `Venta::FORMA_CREDITO`) y `Venta::formaPago()` devuelve la etiqueta. No se deriva del saldo: una venta a crédito ya pagada sigue mostrándose como "Crédito".
- Cliente obligatorio, vendedor = usuario autenticado, consecutivo `VTA-00001`, COP, sin IVA ni datos fiscales.
- Descuento **por línea** (% o monto) con tope en el bruto. Duplicados del mismo producto se permiten como líneas separadas (el stock se valida sumando unidades).
- Contado o crédito: abono inicial (puede ser 0). Si queda saldo, `fecha_vencimiento` es obligatoria; cartera separada con abonos.
- Estados de pago derivados (no se guardan): `pagada` / `pendiente` / `vencida` / `anulada`, calculados con `totalPagado()` / `saldo()` (`withSum('pagos','monto')` cuando está disponible).
- Anulación: solo ventas `confirmada` y **sin abonos**; revierte stock con `MovimientoInventarioService::anular()` (movimientos espejo `anulacion_reversion`). La venta no se borra.
- Sin IVA y sin datos fiscales en el comprobante. Moneda COP.

**Rutas (`/ventas`):** `index`, `crear`, `store`, `{venta}`, `{venta}/recibo` (vista imprimible), `{venta}/anular` (GET/POST), `{venta}/abonos/crear` + `{venta}/abonos`, `cartera`, `reportes/clientes`, `reportes/productos`, `exportar` (CSV). Las rutas estáticas van antes de `{venta}` y éstas usan `whereNumber`.

**Servicios clave:** `VentaService` (crear, descuentos, validación de stock, anular, nuevo switch contado/ Crédito), `PagoVentaService` (registrar abono, valida saldo y método).

**Vistas:** `resources/views/ventas/{index,create,show,recibo,anular,cartera,abonos/create,reportes/clientes,reportes/productos}.blade.php`. El formulario de creación usa un switch `Contado/Crédito` y JS plano para recalcular totales/descuentos en vivo.

**Dashboard:** `InventarioService::resumen()` agrega `ventas_hoy_*`, `ventas_mes_*`, `cartera_pendiente` y `cartera_vencidas`; el dashboard muestra esas tarjetas y la acción rápida "Registrar Venta" apunta a `ventas.create`.

**Menú:** ítems "Ventas" (`fa-cash-register`) y "Cartera" (`fa-wallet`) en `layouts/app.blade.php`.

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
  - `tests/Unit/ConteoFisicoServiceTest.php` — flujo de conteo y generación de ajustes
  - `tests/Feature/MateriaPrimaFeatureTest.php`, `ProductoTerminadoFeatureTest.php`, `MovimientoInventarioFeatureTest.php`
  - `tests/Feature/RecetaFeatureTest.php` — CRUD de recetas/BOM
  - `tests/Feature/ProduccionFeatureTest.php` — producción descuenta MP y suma PT, rechaza sin receta / con stock insuficiente
  - `tests/Feature/ClienteFeatureTest.php` — CRUD de clientes
  - `tests/Unit/VentaServiceTest.php` — motor de stock, reversiones, stock negativo, nuevo switch contado/ Crédito
  - `tests/Feature/VentaFeatureTest.php` — listado, formulario, store, recibo, anulación y CSV
  - `tests/Feature/ReporteVentaFeatureTest.php` — reportes por cliente/producto y filtros de fecha
  - `tests/Feature/ViewsSmokeTest.php` — smoke test de render de todas las vistas (incluyendo recetas, producción, clientes y ventas)

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