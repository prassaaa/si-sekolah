<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# Filament

## Purpose
The entire admin UI, built on Filament v4. Auto-discovered by `AuthPanelProvider` (panel id `auth`, path `/auth`, primary color green). Three building blocks: **Resources** (CRUD per entity), **Pages** (custom report/accounting screens with no single model), and **Widgets** (dashboard + report stat cards/charts).

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Resources/` | ~50 CRUD resources, one folder per domain entity (see `Resources/AGENTS.md`) |
| `Pages/` | Custom pages: financial reports, accounting statements, WhatsApp notif senders (see `Pages/AGENTS.md`) |
| `Widgets/` | Dashboard widgets + per-report stat widgets under `Widgets/Laporan/` (see `Widgets/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Discovery is automatic — drop classes in the right folder, no manual registration.
- Navigation grouping is driven by `$navigationGroup` on each Resource/Page; groups mirror `project.md` (Kesiswaan, Kepegawaian, Akademik, Keuangan, Akuntansi, Laporan, Pengaturan).
- Panel-wide config (logo, colors, sidebar, global search, shield plugin) lives in `app/Providers/Filament/AuthPanelProvider.php`, not here.

### Testing Requirements
- Use Livewire/Filament test helpers in `tests/Feature` (e.g. `livewire(ListSiswas::class)`).

### Common Patterns
- Resources delegate to sibling `Schemas/` (form, infolist) and `Tables/` classes — never inline large schemas.
- Indonesian `navigationLabel` / `modelLabel`.

## Dependencies

### Internal
- `app/Models/` (resource models), `app/Policies/` (authorization)

### External
- `filament/filament` v4, `bezhansalleh/filament-shield`

<!-- MANUAL: -->
