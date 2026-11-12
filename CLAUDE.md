# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

The full project guide (domain, modules, tables, business rules, design system, known issues) lives in `AGENTS.md` and is imported here so it stays the single source of truth. Update `AGENTS.md` when modules or rules change; keep this file for Claude-specific additions only.

@AGENTS.md

## Comandos útiles adicionales

```bash
# Limpiar cachés que apuntan a MySQL RDS antes de correr artisan/tests localmente
php artisan config:clear && php artisan route:clear

# Un solo archivo de test / un solo método
php artisan test tests/Feature/VentaFeatureTest.php
php artisan test --filter=nombre_del_metodo

# Formato de código (Laravel Pint, incluido en require-dev)
./vendor/bin/pint
```

Node no está en el contenedor PHP: `npm run build` / `npx vite build` se corren en el host.

## Arquitectura: puntos que cruzan varios archivos

- **Todo cambio de stock pasa por `MovimientoInventarioService`.** Ventas (`VentaService`), producción con receta (`producir()`), molidos (`MolidoService::producir()`) y conteos físicos (`ConteoFisicoService`) delegan en él para escribir el kardex y actualizar los snapshots `inventario_*` dentro de una transacción. No actualices `inventario_materia_prima` / `inventario_producto_terminado` directamente.
- **Unidades:** materia prima en **gramos** internamente (conversión kg/g solo en forms/vistas); producto terminado en **unidades**; precio de catálogo por paquete de 20 (`ProductoTerminado::UNIDADES_POR_PAQUETE`).
- **Anular, nunca borrar:** movimientos y ventas se revierten con movimientos espejo `anulacion_reversion` (`MovimientoInventarioService::anular()`).

## Módulo Molido (no documentado en AGENTS.md)

Un "molido" es una **materia prima compuesta** producida a partir de otras materias primas:

- `materias_primas.es_molido` (boolean) marca la materia prima como molido; `MateriaPrima::esMolido()`.
- `detalle_molido` — receta del molido: `molido_id`, `ingrediente_id` (ambos → `materias_primas`), `gramos_por_kg` (gramos de ingrediente por 1 kg de molido). Relaciones `detalleMolido()` / inversa por `ingrediente_id`.
- `MolidoService::producir(MateriaPrima $molido, int $gramos, ...)` consume los ingredientes y suma stock al molido vía `MovimientoInventarioService`.
- Rutas: `inventario/materia-prima/{materia}/producir-molido` (GET/POST) → `MolidoController`. Test: `tests/Feature/MolidoFeatureTest.php`.

Otros tests existentes no listados en AGENTS.md: `tests/Feature/CarteraFeatureTest.php`.
