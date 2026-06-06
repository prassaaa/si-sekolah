<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# config

## Purpose
Laravel + package configuration files. Standard framework set plus package configs for the installed ecosystem: `filament`, `filament-shield`, `l5-swagger` (OpenAPI), `activitylog`.

## For AI Agents

### Working In This Directory
- Reference values via `config('key')` everywhere in app code. **Only** config files may call `env()` (per `CLAUDE.md`); never `env()` outside this folder.
- Add new env keys to `.env.example` when introducing them.
- `l5-swagger` config controls where generated API docs land (`storage/api-docs`); regenerate after changing OpenAPI attributes.
- After editing config in production, clear caches (`php artisan config:clear`); the test script already does this.

### Common Patterns
- Each package ships its own `config/<package>.php`; prefer publishing + editing over inline overrides.

## Dependencies

### External
- `filament/filament`, `bezhansalleh/filament-shield`, `darkaonline/l5-swagger`, `spatie/laravel-activitylog`

<!-- MANUAL: -->
