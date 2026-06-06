<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# Widgets

## Purpose
Dashboard widgets and per-report stat cards. Top-level widgets render on the main Dashboard; the `Laporan/` subfolder holds stat widgets paired one-to-one with the report pages.

## Key Files
| File | Description |
|------|-------------|
| `StatsOverviewWidget.php` | Headline dashboard stats |
| `FinancialOverview.php` | Finance summary widget |
| `PembayaranChart.php`, `SiswaChart.php` | Payment / student charts |
| `PendingApprovals.php` | Items awaiting approval (e.g. bukti transfer) |
| `TagihanBelumLunasWidget.php` | Unpaid bills |
| `PresensiHariIniWidget.php`, `PresensiPerJamWidget.php` | RFID attendance — today / per-hour |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Laporan/` | Stat widgets per report page (`LaporanGajiStats`, `LaporanPembayaranStats`, etc.) — each mirrors a page in `../Pages/` |

## For AI Agents

### Working In This Directory
- Scaffold with `php artisan make:filament-widget Xxx --no-interaction`.
- When adding a report page, add the matching `Laporan/XxxStats` widget and embed it via the page's `getHeaderWidgets()`.
- Keep widget queries eager-loaded to avoid N+1 on the dashboard.

### Testing Requirements
- Assert widget data via Livewire test helpers.

### Common Patterns
- Stat widgets extend Filament's stats-overview base; charts extend the chart widget base.

## Dependencies

### Internal
- `app/Models/`, `app/Filament/Pages/` (Laporan widgets pair with report pages)

<!-- MANUAL: -->
