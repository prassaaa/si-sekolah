<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# si-sekolah (SIAKAD)

## Purpose
Indonesian school management system (Sistem Informasi Akademik). A Filament v4 admin panel covering eight domains — Kesiswaan (students), Kepegawaian (staff), Akademik, Keuangan (finance), Akuntansi (accounting), Laporan (reports), Pengaturan (settings), plus a hardware **RFID attendance** subsystem (ESP32 + RC522 readers hitting a JSON API). All UI labels and domain language are Bahasa Indonesia.

## Key Files
| File | Description |
|------|-------------|
| `composer.json` | PHP deps: Laravel 12, Filament 4, filament-shield (RBAC), l5-swagger (OpenAPI), spatie/laravel-activitylog |
| `package.json` | Frontend: Vite 7 + Tailwind 4. Scripts: `npm run build`, `npm run dev` |
| `vite.config.js` | Vite bundling config |
| `phpunit.xml` | Pest/PHPUnit test config |
| `boost.json` | Laravel Boost MCP config |
| `.mcp.json` | MCP server registration |
| `project.md` | Product spec — the full admin menu tree (source of truth for navigation) |
| `CLAUDE.md` | Mandatory coding conventions (Laravel Boost guidelines) |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `app/` | Application code — Filament UI, models, services, HTTP, policies (see `app/AGENTS.md`) |
| `database/` | Migrations, factories, seeders (see `database/AGENTS.md`) |
| `routes/` | Route definitions (see `routes/AGENTS.md`) |
| `tests/` | Pest test suites (see `tests/AGENTS.md`) |
| `config/` | Framework + package config (see `config/AGENTS.md`) |
| `docs/` | Deployment + ESP32 RFID hardware docs (see `docs/AGENTS.md`) |
| `resources/` | Blade views, CSS, JS entrypoints |
| `bootstrap/` | `app.php` — middleware aliases, routing, exceptions (L12 structure) |
| `public/` | Compiled assets + Filament published assets |
| `lang/` | Translation strings |

## For AI Agents

### Working In This Directory
- Read `CLAUDE.md` first — it holds binding conventions (Pint formatting, explicit return types, factory usage, Eloquent-over-DB, named routes).
- Use the **Laravel Boost MCP** tools (`search-docs`, `tinker`, `database-query`, `list-artisan-commands`) before reaching for raw shell.
- Create files with `php artisan make:*` (`--no-interaction`), never hand-roll.
- Domain names are Indonesian; keep naming consistent (e.g. `Siswa`=student, `Pegawai`=staff, `Pembayaran`=payment, `Presensi`=attendance).

### Testing Requirements
- Every change needs a Pest test. Run `php artisan test --compact --filter=Name`.
- Run `vendor/bin/pint --dirty --format agent` before finalizing.

### Common Patterns
- Filament resources are auto-discovered; one resource folder per domain entity.
- Authorization via filament-shield generated policies (`app/Policies/`).
- Activity logging via `spatie/laravel-activitylog` trait on models.

## Dependencies

### External
- Laravel 12, Filament 4, PHP 8.2+
- `bezhansalleh/filament-shield` — role/permission RBAC
- `darkaonline/l5-swagger` — OpenAPI docs for the RFID API
- `spatie/laravel-activitylog` — audit trail

<!-- MANUAL: -->
