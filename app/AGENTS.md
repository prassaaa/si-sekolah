<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# app

## Purpose
Core application code. The bulk is the **Filament v4 admin panel** (one resource per domain entity) backed by ~43 Eloquent models. A small HTTP slice exposes the **RFID scan API** for hardware readers. Authorization is policy-based (filament-shield).

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Filament/` | Admin panel: Resources, custom Pages (reports/accounting), Widgets (see `Filament/AGENTS.md`) |
| `Models/` | ~43 Eloquent models across all domains (see `Models/AGENTS.md`) |
| `Http/` | Controllers, middleware, form requests — RFID API only (see `Http/AGENTS.md`) |
| `Services/` | Domain services; `Rfid/PresensiScanService` holds tap logic (see `Services/AGENTS.md`) |
| `Policies/` | ~45 authorization policies, one per model (see `Policies/AGENTS.md`) |
| `Providers/` | `AppServiceProvider` + `Filament/AuthPanelProvider` panel config (see `Providers/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- The admin panel is the product; most feature work means adding/editing a Filament Resource under `Filament/Resources/`.
- Every model has a matching policy and (almost always) a factory + seeder. Keep the set in sync when adding a model.
- The only non-Filament runtime path is the RFID API (`Http/Controllers/Api/` + `Services/Rfid/`).

### Testing Requirements
- Feature tests for resources/API live in `tests/Feature`; model/service unit tests in `tests/Unit`.

### Common Patterns
- PHP 8 constructor property promotion, explicit return types, PHPDoc array shapes (per `CLAUDE.md`).
- Indonesian domain vocabulary throughout.

## Dependencies

### External
- `filament/filament`, `bezhansalleh/filament-shield`, `spatie/laravel-activitylog`, `darkaonline/l5-swagger`

<!-- MANUAL: -->
