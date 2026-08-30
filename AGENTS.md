# AGENTS.md — sal-ndal

## Project Overview

Laravel 12 + Blade + Tailwind CSS v4 PWA project. Spanish language throughout.

**Domain:** Sistema de gestión para **Santini** — comercializadora de condimentos y especias.

**Modelo de negocio (flujo de inventario):**
1. **Compra** materia prima (condimentos/especias) por **kilogramos**
2. **Almacena** la materia prima
3. **Prepara/empaca** en presentaciones comerciales (bolsas o frascos de 30 g, 50 g, 100 g)
4. **Guarda** como producto terminado
5. **Vende/distribuye** a tiendas, supermercados, restaurantes, mayoristas y otros clientes

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

- Manifest: `public/manifest.json` (app name "MobileApp")
- Service worker: `public/sw.js` (cache-first, pre-caches only `/favicon.ico`)
- Icons: `public/pwa/icons/` — android, ios, windows11 sets
- SW registered in `resources/js/app.js`

**Known issue:** `manifest.json` references `/pwa/icons/maskable-icons/` which doesn't exist on disk.

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

## Known Issues

1. **Dual JS loading:** `welcome.blade.php` (y `layouts/app.blade.php`) cargan JS vía `@vite()` y `asset('js/app.js')` — el service worker se registra dos veces.
2. **Missing maskable icons:** Manifest references icons in `public/pwa/icons/maskable-icons/` — directory missing.
3. **Project name inconsistency:** `.env` says "Laravel", manifest says "MobileApp", HTML says "TuProyecto".
4. **`gestion.blade.php` huérfano:** la vista standalone quedó sin uso tras apuntar `/gestion` al dashboard (se mantiene como referencia de diseño).

## Testing

- PHPUnit 11.5
- Tests use **SQLite in-memory** (`phpunit.xml` overrides DB config)
- Tests del módulo inventario:
  - `tests/Unit/MovimientoInventarioServiceTest.php` — motor de stock, reversiones, stock negativo
  - `tests/Unit/ConteoFisicoServiceTest.php` — flujo de conteo y generación de ajustes
  - `tests/Feature/MateriaPrimaFeatureTest.php`, `ProductoTerminadoFeatureTest.php`, `MovimientoInventarioFeatureTest.php`
  - `tests/Feature/RecetaFeatureTest.php` — CRUD de recetas/BOM
  - `tests/Feature/ProduccionFeatureTest.php` — producción descuenta MP y suma PT, rechaza sin receta / con stock insuficiente
  - `tests/Feature/ViewsSmokeTest.php` — smoke test de render de todas las vistas (incluyendo recetas y producción)

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
