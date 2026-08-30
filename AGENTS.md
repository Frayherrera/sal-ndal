# AGENTS.md — sal-ndal

## Project Overview

Laravel 12 + Blade + Tailwind CSS v4 PWA project. Early stage — only a landing page and default scaffolding exist. Spanish language throughout.

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

## Known Issues

1. **Broken route:** `/gestion` in `routes/web.php` references `view('gestion')` but `gestion.blade.php` does not exist.
2. **Dual JS loading:** `welcome.blade.php` loads JS via both `@vite()` and `asset('js/app.js')` — service worker registers twice.
3. **Missing maskable icons:** Manifest references icons in `public/pwa/icons/maskable-icons/` — directory missing.
4. **Project name inconsistency:** `.env` says "Laravel", manifest says "MobileApp", HTML says "TuProyecto".

## Testing

- PHPUnit 11.5
- Tests use **SQLite in-memory** (`phpunit.xml` overrides DB config)
- Only default example tests exist in `tests/Feature/` and `tests/Unit/`

## Conventions

- Comments and UI text in Spanish
- 4-space indent (`.editorconfig`)
- LF line endings
