<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# Services

## Purpose
Domain service classes holding logic too heavy for controllers or models. Currently the RFID attendance flow.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Rfid/` | `PresensiScanService` — the full tap-to-attendance pipeline |

## `Rfid/PresensiScanService`
Single entry point `handle(RfidDevice $device, string $uid, ?Carbon $scannedAt, array $rawPayload): array`. Pipeline:

1. Normalize UID (uppercase hex, strip separators).
2. Look up `KartuRfid` by UID → `tidak_dikenal` if missing.
3. Reject if card status ≠ `aktif` → `ditolak`.
4. Debounce check against `RfidScanLog` within `Sekolah.debounce_scan_detik` → `duplikat`.
5. In a DB transaction (`lockForUpdate`): route to `PresensiHarian` (siswa) or `PresensiHarianPegawai` (pegawai) based on polymorphic owner.
   - No record today → create **masuk** (status `hadir`/`terlambat` via `calculateMasukStatus` using `jam_masuk_default` + `batas_terlambat_menit`).
   - Record exists, no `jam_pulang` → update **pulang** (guarded by `jam_pulang_minimal`).
   - Already has `jam_pulang` → `duplikat`.
6. Always writes a `RfidScanLog` audit row and calls `$device->tandaiAktif()`.

## For AI Agents

### Working In This Directory
- Keep this service the single source of tap logic; the controller stays thin.
- Returns a plain array (the API JSON body) keyed by `success`, `jenis`, `pesan`, `pemilik`, `presensi`. Preserve that shape — the OpenAPI docs and tests depend on it.
- Attendance thresholds come from the `Sekolah` settings row, not constants.

### Testing Requirements
- Covered by `tests/Unit/PresensiScanServiceTest.php`. Add cases for every `jenis` branch when changing flow.

### Common Patterns
- `CarbonImmutable` for scan timestamps; transactional write + `lockForUpdate` to avoid double-tap races.

## Dependencies

### Internal
- `app/Models/`: `KartuRfid`, `RfidDevice`, `RfidScanLog`, `PresensiHarian`, `PresensiHarianPegawai`, `Pegawai`, `Sekolah`

<!-- MANUAL: -->
