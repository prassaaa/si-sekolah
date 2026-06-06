<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# database

## Purpose
Schema, fake-data factories, and seeders. ~51 migrations, ~41 factories, ~44 seeders — roughly one of each per model. Default dev DB is SQLite (`database/database.sqlite`, created by composer scripts).

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `migrations/` | ~51 schema migrations (tables, columns, indexes, FKs) |
| `factories/` | Model factories for tests/seeding; rich states in `SiswaFactory`, `KelasFactory`, `TahfidzFactory` |
| `seeders/` | Per-model seeders + `DatabaseSeeder` orchestrator; `RoleSeeder` defines shield roles/permissions |

## For AI Agents

### Working In This Directory
- Generate via artisan: `make:migration`, `make:factory`, `make:seeder` (`--no-interaction`).
- **Column modifications must restate all existing attributes** (Laravel 12) or they get dropped.
- Register new seeders in `DatabaseSeeder::run()` in dependency order (roles → reference data → transactional data).
- Seeders are heavy and realistic (e.g. `JurnalUmumSeeder` ~13KB, `RoleSeeder` ~10KB) — they encode real domain relationships; mirror that when adding data.
- Use factories in tests and within seeders rather than manual `create()` arrays where states exist.

### Testing Requirements
- Tests use factories (`RefreshDatabase`); never depend on seeded prod-like data in unit tests.

### Common Patterns
- Indonesian table/column names matching the models.

## Dependencies

### Internal
- `app/Models/` (factories/seeders target models)

### External
- `fakerphp/faker`, `bezhansalleh/filament-shield` (RoleSeeder)

<!-- MANUAL: -->
