<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-06-06 | Updated: 2026-06-06 -->

# Models

## Purpose
~43 Eloquent models spanning every domain. Many use `SoftDeletes` and the `spatie/laravel-activitylog` `LogsActivity` trait for an audit trail. Casts are declared in a `casts()` method (Laravel 12 convention).

## Key Files (by domain)
| Domain | Models |
|--------|--------|
| Kesiswaan | `Siswa`, `Kelas`, `Tahfidz`, `IzinKeluar`, `IzinPulang`, `Prestasi`, `Pelanggaran`, `Konseling` |
| Kepegawaian | `Pegawai`, `JabatanPegawai` |
| Akademik | `TahunAjaran`, `Semester`, `MataPelajaran`, `JamPelajaran`, `JadwalPelajaran`, `KenaikanKelas`, `Kelulusan` |
| Keuangan | `Pembayaran`, `PembayaranPaket`, `BuktiTransfer`, `JenisPembayaran`, `KategoriPembayaran`, `PosBayar`, `Pajak`, `UnitPos`, `TabunganSiswa`, `TagihanSiswa`, `SaldoAwal`, `KasKeluar`, `KasMasuk` |
| Penggajian | `SettingGaji`, `SlipGaji` |
| Akuntansi | `Akun`, `JurnalUmum` |
| Presensi/RFID | `KartuRfid` (polymorphic `owner`), `RfidDevice`, `RfidScanLog`, `PresensiHarian`, `PresensiHarianPegawai`, `Absensi` |
| Settings | `Sekolah`, `Informasi`, `User` |

## For AI Agents

### Working In This Directory
- Create with `php artisan make:model Xxx -mfs` (migration + factory + seeder) and keep the matching `app/Policies/XxxPolicy.php` in sync.
- Use typed relationship methods (`BelongsTo`, `HasMany`, `MorphOne`, etc.) with return type hints. `KartuRfid` owner is **polymorphic** — owner is either a `Siswa` or `Pegawai`, which `PresensiScanService` branches on.
- `Sekolah` is a singleton-style settings row holding attendance config: `jam_masuk_default`, `batas_terlambat_menit`, `jam_pulang_minimal`, `debounce_scan_detik`.
- Declare casts in `casts()`, not `$casts`. Prefer Eloquent over `DB::`.

### Testing Requirements
- Model logic (scopes, accessors, relationships) → `tests/Unit` (see `KartuRfidModelTest`, `PresensiHarianModelTest`). Use factories.

### Common Patterns
- `SoftDeletes` + `LogsActivity` on most domain models; Indonesian column names.

## Dependencies

### Internal
- `database/factories/`, `database/migrations/`

### External
- `spatie/laravel-activitylog`

<!-- MANUAL: -->
