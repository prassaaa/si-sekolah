<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# Pages

## Purpose
Custom Filament pages that are **not** simple CRUD over one model — financial/accounting reports, aggregate dashboards, and WhatsApp notification senders. These power the Akuntansi and Laporan menu groups plus a few action pages.

## Key Files
| File | Description |
|------|-------------|
| `LaporanKeuangan.php`, `LaporanPembayaran.php`, `LaporanPembayaranPerKelas.php`, `LaporanPembayaranPerTanggal.php` | Finance/payment reports |
| `LaporanSiswa.php`, `LaporanTahfidz.php`, `LaporanTabungan.php`, `LaporanTagihanSiswa.php` | Student / savings / billing reports |
| `LaporanGaji.php`, `LaporanJurnal.php`, `LaporanUnitPos.php`, `LaporanDebitKredit.php` | Payroll / journal / POS / debit-credit reports |
| `JurnalUmum` views: `BukuBesar.php`, `ArusKasBank.php`, `LabaRugi.php`, `Neraca.php`, `PerubahanModal.php` | Accounting statements (general ledger, cash flow, P&L, balance sheet, equity) |
| `KirimNotifGaji.php`, `KirimTagihan.php`, `KirimNotifPresensi.php` | WhatsApp notification sender pages |
| `MonitorGerbang.php` | Live RFID gate monitor |

## For AI Agents

### Working In This Directory
- Scaffold with `php artisan make:filament-page Xxx --no-interaction`.
- Pages query models directly (aggregations/reports); keep heavy computation testable — extract to a service if it grows.
- Each report page typically pairs with a stat widget in `Widgets/Laporan/` (e.g. `LaporanGaji` ↔ `LaporanGajiStats`).
- Blade views for pages live under `resources/views/filament/pages/`.

### Testing Requirements
- Cover report math and filters with Feature tests rather than eyeballing the UI.

### Common Patterns
- Indonesian labels; reports are filter-driven (date range, kelas, etc.).

## Dependencies

### Internal
- `app/Models/` (read-heavy queries), `app/Filament/Widgets/Laporan/` (paired stat cards)

<!-- MANUAL: -->
