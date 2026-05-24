# Plan: Fitur Presensi Harian + RFID

**Status**: Draft - Menunggu approval untuk mulai implementasi
**Author**: Sisyphus
**Tanggal**: 2026-05-24
**Target**: Sistem absensi gerbang sekolah berbasis kartu RFID, terpisah dari absensi per-pelajaran yang sudah ada.

---

## 1. Tujuan & Ruang Lingkup

### 1.1 Tujuan

Menambahkan sistem **presensi harian** untuk siswa yang dipicu oleh tap kartu RFID di gerbang sekolah, dengan kemampuan:

- Catat tap masuk + tap pulang per siswa per hari
- Status otomatis: `hadir`, `terlambat`, `alpha`
- Override manual oleh petugas piket (`izin`, `sakit`)
- Audit lengkap semua aktivitas tap (termasuk yang gagal/tidak dikenal)
- Manajemen kartu (registrasi, hilang, ganti, nonaktif)
- Notifikasi WhatsApp ke wali murid saat anak tap masuk (reuse infra existing)

### 1.2 Bukan Bagian dari Plan ini (Out of Scope)

- Presensi pegawai berbasis RFID (phase 2 — pakai pattern yang sama, tabel terpisah)
- Auto-link ke `absensis` per-pelajaran (independent by design)
- Integrasi ke akuntansi/keuangan
- Mobile app untuk siswa/wali (hanya hardware reader yang menulis ke API)

### 1.3 Konvensi yang Dipakai

- Nama tabel: plural Indonesia (sesuai existing: `siswas`, `tagihan_siswas`)
- Foreign key: `siswa_id` → `siswas.id` (BUKAN ke `nis`)
- Status enum: lowercase Indonesia, konsisten dgn `Absensi` existing
- SoftDeletes + LogsActivity (Spatie) di semua model bisnis
- Filament Resource: split-folder (`Resource.php` + `Schemas/` + `Tables/` + `Pages/`)
- Permission naming: `{Action}:{Resource}` via Filament Shield

---

## 2. Skema Database

### 2.1 Tabel Baru: `presensi_harians`

Rekap kehadiran per siswa per hari. **Source of truth** untuk laporan presensi.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AI | |
| `siswa_id` | FK → `siswas.id` | restrictOnDelete |
| `tanggal` | DATE | |
| `jam_masuk` | TIME NULL | |
| `jam_pulang` | TIME NULL | |
| `status` | ENUM | `hadir`, `terlambat`, `izin`, `sakit`, `alpha` |
| `sumber_masuk` | ENUM NULL | `rfid`, `manual`, `import` |
| `sumber_pulang` | ENUM NULL | `rfid`, `manual`, `import` |
| `terlambat_menit` | INT NULL | denormalisasi untuk reporting cepat |
| `keterangan` | TEXT NULL | |
| `dicatat_oleh` | FK → `users.id` NULL | null = sistem (RFID otomatis) |
| `created_at`, `updated_at`, `deleted_at` | timestamps + softDeletes | |

**Index & Constraint**:
- `UNIQUE (siswa_id, tanggal)` — satu siswa satu record per hari
- `INDEX (tanggal)` — query laporan harian
- `INDEX (status, tanggal)` — widget dashboard
- `INDEX (siswa_id, tanggal)` — riwayat siswa

### 2.2 Tabel Baru: `kartu_rfids`

Registry kartu RFID per siswa. Menyimpan **riwayat lengkap** kartu (siswa bisa ganti kartu kalau hilang/rusak).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AI | |
| `siswa_id` | FK → `siswas.id` | restrictOnDelete |
| `uid` | VARCHAR(32) | UID kartu, hex string (4-10 bytes) |
| `status` | ENUM | `aktif`, `nonaktif`, `hilang`, `rusak` |
| `diaktifkan_pada` | DATETIME | |
| `dinonaktifkan_pada` | DATETIME NULL | |
| `keterangan` | TEXT NULL | |
| `created_at`, `updated_at`, `deleted_at` | timestamps + softDeletes | |

**Index & Constraint**:
- `UNIQUE (uid)` — satu UID hanya ada di satu record (kartu fisik unik)
- `INDEX (siswa_id, status)` — query "kartu aktif siswa X"

**Business rule (validasi level aplikasi)**:
- Satu siswa hanya boleh punya **1 kartu dengan status `aktif`** pada satu waktu
- Saat kartu baru `aktif` ditambahkan, kartu lama auto-`nonaktif` (dengan konfirmasi)
- Saat scan, hanya kartu `aktif` yang valid

### 2.3 Tabel Baru: `rfid_devices`

Registry reader hardware. Setiap device punya token unik untuk auth API.

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AI | |
| `nama` | VARCHAR(100) | mis. "Gerbang Utama Masuk" |
| `kode` | VARCHAR(50) UNIQUE | mis. "GERBANG-IN-01" |
| `jenis` | ENUM | `gerbang_masuk`, `gerbang_pulang`, `serbaguna` |
| `lokasi` | VARCHAR(150) NULL | |
| `api_token` | VARCHAR(80) UNIQUE | hashed (pakai `Hash::make`) |
| `terakhir_aktif` | DATETIME NULL | update tiap scan |
| `is_active` | BOOLEAN | default true |
| `keterangan` | TEXT NULL | |
| `created_at`, `updated_at` | timestamps | |

**Catatan**: token disimpan **hashed** seperti `personal_access_tokens`. Saat create, plain token ditampilkan **sekali** ke admin untuk di-copy ke firmware.

### 2.4 Tabel Baru: `rfid_scan_logs`

Audit log SEMUA tap, termasuk yang gagal. Tidak ada delete (append-only).

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | BIGINT PK AI | |
| `uid` | VARCHAR(32) | selalu disimpan apa adanya |
| `kartu_rfid_id` | FK → `kartu_rfids.id` NULL | null kalau UID tidak dikenal |
| `siswa_id` | FK → `siswas.id` NULL | null kalau UID unknown |
| `rfid_device_id` | FK → `rfid_devices.id` NULL | |
| `jenis` | ENUM | `masuk`, `pulang`, `duplikat`, `ditolak`, `tidak_dikenal` |
| `pesan` | VARCHAR(255) | pesan untuk display di device |
| `request_payload` | JSON NULL | raw request dari device |
| `response_payload` | JSON NULL | response yang dikirim balik |
| `scanned_at` | DATETIME | timestamp dari device (bukan server) |
| `created_at` | TIMESTAMP | timestamp server (untuk deteksi delay) |

**Index**:
- `INDEX (scanned_at)`
- `INDEX (siswa_id, scanned_at)`
- `INDEX (uid, scanned_at)`
- `INDEX (rfid_device_id, scanned_at)`
- `INDEX (jenis, scanned_at)` — query "berapa banyak ditolak hari ini"

**Tidak pakai SoftDeletes** — log harus immutable.

### 2.5 Tabel Diubah: `sekolahs`

Tambah kolom konfigurasi presensi:

| Kolom Baru | Tipe | Default | Keterangan |
|---|---|---|---|
| `jam_masuk_default` | TIME | `07:00:00` | jam masuk normal |
| `batas_terlambat_menit` | INT | `15` | tap setelah `jam_masuk + N menit` = terlambat |
| `jam_pulang_minimal` | TIME | `12:00:00` | tap sebelum jam ini bukan dianggap pulang |
| `debounce_scan_detik` | INT | `60` | tap dlm window ini = duplikat |

**Catatan**: kolom `notif_presensi_aktif` **TIDAK** ditambahkan di phase ini. Ditambah belakangan saat WA sungguhan diimplementasi (YAGNI).

---

## 3. Model & Relasi

### 3.1 Model Baru

| Model | Tabel | Traits |
|---|---|---|
| `PresensiHarian` | `presensi_harians` | `HasFactory`, `LogsActivity`, `SoftDeletes` |
| `KartuRfid` | `kartu_rfids` | `HasFactory`, `LogsActivity`, `SoftDeletes` |
| `RfidDevice` | `rfid_devices` | `HasFactory`, `LogsActivity` |
| `RfidScanLog` | `rfid_scan_logs` | `HasFactory` (no LogsActivity — log itself, no soft delete) |

### 3.2 Relasi

**`Siswa`** (existing, ditambahkan):
- `presensiHarians(): HasMany` → `PresensiHarian`
- `kartuRfids(): HasMany` → `KartuRfid`
- `kartuRfidAktif(): HasOne` → `KartuRfid` where status=aktif

**`PresensiHarian`**:
- `siswa(): BelongsTo`
- `pencatat(): BelongsTo` → User (kolom `dicatat_oleh`)

**`KartuRfid`**:
- `siswa(): BelongsTo`
- `scanLogs(): HasMany` → `RfidScanLog`

**`RfidDevice`**:
- `scanLogs(): HasMany` → `RfidScanLog`

**`RfidScanLog`**:
- `siswa(): BelongsTo` (nullable)
- `kartuRfid(): BelongsTo` (nullable)
- `device(): BelongsTo` → `RfidDevice` (nullable)

### 3.3 Business Logic di Model

**`PresensiHarian`**:
- Scope: `hariIni()`, `bulanIni()`, `byStatus(string)`, `terlambatSaja()`
- Accessor: `getStatusInfoAttribute()` — `['label' => ..., 'color' => ...]`
- Method: `isHadir()`, `isTerlambat()`, `sudahPulang()`

**`KartuRfid`**:
- Scope: `aktif()`, `byUid(string)`
- Observer/booted: saat `creating` dgn status=aktif → set kartu lain milik siswa yg sama jadi nonaktif
- Method: `nonaktifkan(string $alasan)`, `tandaiHilang()`

**`RfidDevice`**:
- Method: `generateToken()` — return plain token + simpan hashed
- Method: `verifyToken(string $plain)` — `Hash::check`
- Scope: `aktif()`

---

## 4. API Endpoint untuk Hardware Reader

### 4.1 Endpoint

```
POST /api/rfid/scan
Authorization: Bearer {device_api_token}
Content-Type: application/json

{
  "uid": "04A1B2C3D4",
  "scanned_at": "2026-05-24T07:05:32+07:00",
  "device_kode": "GERBANG-IN-01"  // optional, double-check
}
```

### 4.2 Response

**Sukses (200)**:
```json
{
  "success": true,
  "jenis": "masuk",
  "siswa": {
    "nama": "Ahmad Setiawan",
    "kelas": "VII-A",
    "foto_url": "https://..."
  },
  "presensi": {
    "status": "terlambat",
    "jam_masuk": "07:05:32",
    "terlambat_menit": 5
  },
  "pesan": "Selamat datang Ahmad. Anda terlambat 5 menit."
}
```

**Ditolak (200, success=false)**:
```json
{
  "success": false,
  "jenis": "ditolak",
  "pesan": "Kartu sudah dinonaktifkan. Hubungi TU."
}
```

**UID tidak dikenal (200, success=false)**:
```json
{
  "success": false,
  "jenis": "tidak_dikenal",
  "pesan": "Kartu tidak terdaftar."
}
```

**Auth gagal (401)**:
```json
{ "message": "Unauthorized" }
```

### 4.3 Logika Server (alur request)

1. Auth: `Bearer` token → cari `RfidDevice` aktif dgn `Hash::check`. Gagal → 401.
2. Validasi payload (FormRequest): `uid` required string, `scanned_at` required datetime.
3. Insert `rfid_scan_logs` dgn `request_payload` (selalu, sebelum proses) — audit lengkap.
4. Cari `KartuRfid` by `uid`:
   - Tidak ada → log `jenis=tidak_dikenal`, response error
   - Ada tapi status ≠ `aktif` → log `jenis=ditolak`, response error dgn alasan status
5. Cek **debounce window**: scan terakhir untuk `uid` ini dalam X detik (config `debounce_scan_detik`)?
   - Ya → log `jenis=duplikat`, response info "tap terlalu cepat"
6. Tentukan **jenis tap** (masuk vs pulang):
   - Cari `presensi_harians` untuk siswa+tanggal hari ini
   - Tidak ada → tap pertama = `masuk` → create record baru, set `jam_masuk`, hitung status (`hadir`/`terlambat` berdasarkan `jam_masuk_default` + `batas_terlambat_menit`)
   - Ada `jam_masuk` tapi belum `jam_pulang`:
     - Jika `now() < jam_pulang_minimal` → tolak ("belum waktunya pulang")
     - Else → tap = `pulang`, update `jam_pulang`
   - Sudah ada `jam_pulang` → log `jenis=duplikat` ("sudah tap pulang")
7. Update `rfid_scan_logs` dgn `kartu_rfid_id`, `siswa_id`, `jenis`, `pesan`, `response_payload`.
8. Update `RfidDevice.terakhir_aktif`.
9. (Opsional) Dispatch job notifikasi WhatsApp ke wali jika `notif_presensi_aktif=true` dan jenis=masuk.
10. Return JSON response.

### 4.4 Routes

Tambah file baru `routes/api.php` (belum ada di project) atau register di `bootstrap/app.php`.

```php
// bootstrap/app.php — withRouting()
api: __DIR__.'/../routes/api.php',
```

```php
// routes/api.php
use App\Http\Controllers\Api\RfidScanController;

Route::middleware('rfid.device')->group(function () {
    Route::post('/rfid/scan', [RfidScanController::class, 'store']);
});
```

### 4.5 Komponen API

| Komponen | Path |
|---|---|
| Controller | `app/Http/Controllers/Api/RfidScanController.php` |
| FormRequest | `app/Http/Requests/RfidScanRequest.php` |
| Middleware | `app/Http/Middleware/AuthenticateRfidDevice.php` |
| Service | `app/Services/Rfid/PresensiScanService.php` |
| Resource | `app/Http/Resources/RfidScanResource.php` |

**Service-layer**: `PresensiScanService` punya method `handle(RfidDevice $device, string $uid, Carbon $scannedAt): array` — mudah di-test unit, controller tipis.

---

## 5. Filament Resources

### 5.1 `PresensiHarianResource` (Group: Kesiswaan)

**Lokasi**: `app/Filament/Resources/PresensiHarians/`

**Tabel (List)**:
- Kolom: Tanggal, NIS, Nama, Kelas, Jam Masuk, Jam Pulang, Status (badge), Sumber Masuk, Terlambat (menit)
- Filter: tanggal range, status, kelas, sumber, "tidak hadir saja"
- Default sort: `tanggal desc, jam_masuk asc`
- Bulk action: export CSV/Excel
- Header action: **"Input Manual"** + **"Tandai Alpha Massal"** (untuk akhir hari, tandai siswa belum tap)

**Form (Create/Edit Manual)**:
- Section "Identitas": Siswa (searchable), Tanggal
- Section "Kehadiran": Status, Jam Masuk, Jam Pulang
- Section "Override": Sumber (auto: `manual`), Keterangan, Dicatat Oleh (auto: `auth()->id()`)

**Page tambahan**:
- `MonitorPresensi` — realtime feed scan (Livewire `wire:poll.5s`), card layout per scan terakhir, filter per device

### 5.2 `KartuRfidResource` (Group: Kesiswaan)

**Lokasi**: `app/Filament/Resources/KartuRfids/`

**Tabel (List)**:
- Kolom: UID, Siswa (NIS - Nama), Kelas, Status (badge), Diaktifkan, Dinonaktifkan
- Filter: status, kelas
- Action per-row: "Nonaktifkan", "Tandai Hilang", "Cetak Kartu" (placeholder phase 2)

**Form (Create/Edit)**:
- Siswa (searchable, exclude yang sudah punya kartu aktif — atau tampilkan warning)
- UID (input manual atau **scan via device** — phase 2)
- Status (default `aktif`)
- Diaktifkan Pada (default now)
- Keterangan

**Header action**: "Daftarkan Massal" (CSV import: kolom NIS, UID)

### 5.3 `RfidDeviceResource` (Group: Pengaturan)

**Lokasi**: `app/Filament/Resources/RfidDevices/`

**Tabel (List)**:
- Kolom: Nama, Kode, Jenis (badge), Lokasi, Terakhir Aktif, Status
- Indicator status koneksi: hijau jika `terakhir_aktif` < 5 menit, kuning < 1 jam, merah jika lebih lama

**Form**:
- Nama, Kode (validasi: alphanumeric + dash, unique)
- Jenis, Lokasi
- Is Active toggle

**Custom action saat create**:
- Setelah save, tampilkan **modal sekali tampil** dgn `api_token` plain text + tombol "Copy" + warning "Token hanya ditampilkan sekali. Simpan sekarang."
- Action "Regenerate Token" di edit page (dgn konfirmasi)

### 5.4 `RfidScanLogResource` (Group: Pengaturan, **read-only**)

**Lokasi**: `app/Filament/Resources/RfidScanLogs/`

**Tabel (List)**:
- Kolom: Scanned At, UID, Siswa, Device, Jenis (badge dgn warna), Pesan
- Filter: tanggal range, jenis, device, "ditolak/tidak_dikenal saja"
- Default sort: `scanned_at desc`
- Pagination: 50 per page

**ViewPage**:
- Show full `request_payload` & `response_payload` (JSON pretty print)

**Tidak ada Create/Edit/Delete** — log immutable.

### 5.5 Update Existing: `SekolahResource`

Tambah section "Konfigurasi Presensi RFID" di form:
- Jam Masuk Default (TimePicker)
- Batas Terlambat (menit, NumberInput)
- Jam Pulang Minimal (TimePicker)
- Debounce Scan (detik, NumberInput)
- Notif WA Presensi Aktif (Toggle)


---

## 6. Permission & Role Updates

### 6.1 Resource Baru di `RoleSeeder.php`

Tambahkan ke `$allResources`:
- `PresensiHarian`
- `KartuRfid`
- `RfidDevice`
- `RfidScanLog`

### 6.2 Mapping Permission per Role

**`super_admin`**: full access semua (otomatis via `Permission::all()`)

**`tata_usaha`** (tambahkan ke `getTataUsahaPermissions()`):
- `fullCrud('PresensiHarian')` — bisa override manual + delete
- `fullCrud('KartuRfid')` — administrasi kartu
- `fullCrud('RfidDevice')` — register device
- `viewOnly('RfidScanLog')` — debugging

**`petugas_piket`** (tambahkan ke `getPetugasPiketPermissions()`):
- `noDelete('PresensiHarian')` — input manual + edit, no delete
- `viewOnly('KartuRfid')` — cek apakah kartu siswa aktif
- `viewOnly('RfidScanLog')` — monitoring tap

**`wali_kelas`** (tambahkan ke `getWaliKelasPermissions()`):
- `viewOnly('PresensiHarian')` — pantau presensi anak walinya
- *(scoping per-kelas via Policy, bukan permission)*

**`guru_bk`** (tambahkan ke `getGuruBkPermissions()`):
- `viewOnly('PresensiHarian')` — identifikasi siswa sering alpha

**`bendahara`, `guru`**: TIDAK perlu akses (presensi bukan domain mereka)

### 6.3 Policy Scoping (penting untuk `wali_kelas`)

`PresensiHarianPolicy::viewAny()` & `view()` perlu scoping:
- `super_admin`, `tata_usaha`, `petugas_piket`, `guru_bk` → semua data
- `wali_kelas` → hanya siswa di `Kelas` yang dia jadi `wali_kelas_id`

Ini memerlukan **global scope** atau **manual filter di Resource**:
```php
// di PresensiHarianResource::getEloquentQuery()
$query = parent::getEloquentQuery();

if (auth()->user()->hasRole('wali_kelas')) {
    $kelasIds = Pegawai::where('user_id', auth()->id())
        ->first()?->kelasWaliKelas->pluck('id') ?? collect();
    $query->whereHas('siswa', fn ($q) => $q->whereIn('kelas_id', $kelasIds));
}

return $query;
```

---

## 7. Notifikasi WhatsApp ke Wali Murid (Coming Soon — Placeholder)

### 7.1 Status

**TIDAK diimplementasi nyata di phase ini.** Mengikuti pattern existing:
- [KirimNotifGaji.php](file:///Users/prasa/Project/Laravel/si-sekolah/app/Filament/Pages/KirimNotifGaji.php) — placeholder "Coming Soon"
- [KirimTagihan.php](file:///Users/prasa/Project/Laravel/si-sekolah/app/Filament/Pages/KirimTagihan.php) — placeholder "Coming Soon"

Belum ada `WhatsappService` real di codebase. Notif presensi mengikuti pattern yang sama: **buat placeholder page** sebagai tempat fitur ini akan tinggal nanti.

### 7.2 Placeholder Page

**File**: `app/Filament/Pages/KirimNotifPresensi.php`

Pattern persis seperti `KirimNotifGaji`:
- Navigation group: `Notifikasi`
- Navigation badge: `Soon`
- Navigation icon: `Heroicon::OutlinedBellAlert`
- View: `filament::pages.placeholder` (built-in Filament)
- `mount()`: tampilkan notification "Coming Soon" + redirect back

```php
<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class KirimNotifPresensi extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static \UnitEnum|string|null $navigationGroup = 'Notifikasi';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Kirim Notif Presensi';

    protected static ?string $navigationLabel = 'Kirim Notif Presensi';

    protected static ?string $navigationBadge = 'Soon';

    protected string $view = 'filament::pages.placeholder';

    public function mount(): void
    {
        Notification::make()
            ->title('Coming Soon')
            ->body('Fitur Kirim Notifikasi Presensi via WhatsApp sedang dalam pengembangan.')
            ->info()
            ->send();

        $this->redirect(url()->previous());
    }
}
```

### 7.3 Persiapan untuk Implementasi Nyata Nanti

Saat WA service sungguhan dibangun (dependent task, di luar plan ini):
1. Tambah kolom `notif_presensi_aktif` ke `sekolahs` (migration baru)
2. Buat `App\Services\WhatsappService` (interface + implementation per provider)
3. Buat `KirimNotifPresensiMasuk` job
4. Update `PresensiScanService` untuk dispatch job
5. Replace placeholder page dgn UI sungguhan (form pilih siswa/range tanggal, send manual)

`PresensiScanService` di Phase 3 cukup tinggalkan **TODO comment** di tempat yang tepat:

```php
// TODO(WA-Notif): dispatch KirimNotifPresensiMasuk job here when WhatsappService is ready
```

---

## 8. Dashboard Widget

### 8.1 `PresensiHariIniWidget` (Stats)

**Lokasi**: `app/Filament/Widgets/PresensiHariIniWidget.php`

Stats card:
- **Hadir**: jumlah siswa dgn status `hadir` hari ini (color: success)
- **Terlambat**: jumlah dgn status `terlambat` (color: warning)
- **Belum Tap**: total siswa aktif - sudah tap (color: gray)
- **Alpha**: hari ini status `alpha` (color: danger, hanya muncul setelah jam pulang)

Filter periode: hari ini default, bisa pilih tanggal lain.

### 8.2 `PresensiPerJamWidget` (Chart)

**Lokasi**: `app/Filament/Widgets/PresensiPerJamWidget.php`

Bar chart: distribusi tap masuk per jam (06:00, 06:30, ..., 08:00). Berguna untuk:
- Lihat jam puncak gerbang
- Identifikasi pola keterlambatan

### 8.3 Update Existing `StatsOverviewWidget`

Tidak perlu diubah — biarkan stats finansial/siswa global di sana. Widget presensi terpisah supaya bisa di-permission per role.


---

## 9. Testing Strategy

### 9.1 Pattern: ikuti `tests/Feature/AbsensiTest.php`

[AbsensiTest.php](file:///Users/prasa/Project/Laravel/si-sekolah/tests/Feature/AbsensiTest.php) adalah template bagus — pattern Pest + Livewire Filament resource testing. Reuse pattern ini.

### 9.2 Feature Test Files

| File | Cakupan |
|---|---|
| `tests/Feature/PresensiHarianResourceTest.php` | CRUD via Filament (list, create manual, edit, delete, filter, validasi) |
| `tests/Feature/KartuRfidResourceTest.php` | CRUD kartu, business rule "1 kartu aktif per siswa" |
| `tests/Feature/RfidDeviceResourceTest.php` | CRUD device, generate token sekali tampil |
| `tests/Feature/RfidScanLogResourceTest.php` | Read-only enforcement |
| `tests/Feature/Api/RfidScanApiTest.php` | API endpoint — happy paths + error paths |
| `tests/Unit/PresensiScanServiceTest.php` | Logic service (debounce, masuk vs pulang, status calculation) |

### 9.3 Test Scenarios Wajib

**`RfidScanApiTest`** — minimal scenario:
- ✅ Tap pertama hari ini → create `presensi_harians` dgn `jam_masuk`, status `hadir`
- ✅ Tap setelah `jam_masuk_default + batas_terlambat_menit` → status `terlambat`
- ✅ Tap kedua setelah `jam_pulang_minimal` → update `jam_pulang`
- ✅ Tap kedua sebelum `jam_pulang_minimal` → log `ditolak`, response error
- ✅ Tap dlm window debounce → log `duplikat`
- ✅ UID tidak terdaftar → log `tidak_dikenal`
- ✅ Kartu status `nonaktif`/`hilang` → log `ditolak`
- ✅ Tanpa `Bearer` token → 401
- ✅ Token salah → 401
- ✅ Device `is_active=false` → 401
- ✅ Setiap scan (sukses/gagal) menulis ke `rfid_scan_logs`

**`KartuRfidResourceTest`**:
- ✅ Create kartu aktif baru → kartu lama (kalau ada) auto-nonaktif
- ✅ UID duplikat → validation error
- ✅ Tandai hilang → status update + `dinonaktifkan_pada` terisi

**`PresensiHarianResourceTest`**:
- ✅ Wali kelas hanya lihat siswa kelasnya (test scoping)
- ✅ Tata usaha lihat semua
- ✅ Manual input → `sumber_masuk='manual'`, `dicatat_oleh=user.id`
- ✅ Filter status, tanggal, kelas

### 9.4 Browser Test

**Skip** — sesuai keputusan user. Cukup Pest feature/unit test untuk semua scenario. Manual smoke test untuk halaman `MonitorGerbang` saat phase 4.


---

## 10. Implementation Phases

Implementasi dibagi 5 phase. **Setiap phase wajib lulus test sebelum lanjut**. Ini supaya bisa di-review per phase, dan kalau ada blocker, bisa stop tanpa setengah jadi.

### Phase 1: Foundation (Database + Models)

**Deliverables**:
- 5 migration: `presensi_harians`, `kartu_rfids`, `rfid_devices`, `rfid_scan_logs`, alter `sekolahs`
- 4 model: `PresensiHarian`, `KartuRfid`, `RfidDevice`, `RfidScanLog`
- 4 factory + 4 seeder
- Update `Siswa` model: tambah relasi `presensiHarians()`, `kartuRfids()`
- Update `RoleSeeder`: tambah resource & permission baru
- Unit test untuk model business logic (booted hooks, scopes, accessors)

**Acceptance**:
- `php artisan migrate:fresh --seed` sukses
- `vendor/bin/pint --dirty --format agent` clean
- Unit test model lulus

### Phase 2: Filament Resources (CRUD UI)

**Deliverables**:
- 4 Resource: `PresensiHarianResource`, `KartuRfidResource`, `RfidDeviceResource`, `RfidScanLogResource`
- Update `SekolahResource` form (tambah section RFID)
- Policy untuk 4 model (extend pattern existing dari Filament Shield)
- Custom action: regenerate token, tandai hilang, nonaktifkan kartu

**Acceptance**:
- Manual smoke test via browser: login `tata_usaha`, akses semua CRUD baru, no error
- Feature test (Livewire) untuk tiap Resource lulus
- Permission scoping (wali kelas, BK) terverifikasi

### Phase 3: API Endpoint

**Deliverables**:
- Register `routes/api.php` di `bootstrap/app.php`
- `RfidScanController`, `RfidScanRequest`, `AuthenticateRfidDevice` middleware
- `PresensiScanService` dgn semua logic (debounce, masuk/pulang, status)
- Feature test API: minimal 11 scenario di section 9.3

**Acceptance**:
- Semua API test lulus
- Manual test pakai `curl`/Postman: scan request → response sesuai spec
- Audit log selalu tertulis (cek `rfid_scan_logs` setelah tiap test)

### Phase 4: Dashboard & Realtime Monitoring

**Deliverables**:
- `PresensiHariIniWidget` (stats)
- `PresensiPerJamWidget` (chart)
- `MonitorGerbang` page dgn Livewire polling
- Permission widget: hanya tampil untuk role yang relevan

**Acceptance**:
- Widget tampil di dashboard dgn data benar
- Monitor Gerbang auto-refresh saat ada scan baru (test manual: trigger API → watch UI)

### Phase 5: Notifikasi WhatsApp Placeholder (Coming Soon)

**Deliverables**:
- 1 file: `app/Filament/Pages/KirimNotifPresensi.php` — placeholder page mengikuti pattern [KirimNotifGaji.php](file:///Users/prasa/Project/Laravel/si-sekolah/app/Filament/Pages/KirimNotifGaji.php)
- Permission `Page` access (otomatis lewat Filament Shield kalau perlu)
- TODO comment di `PresensiScanService` untuk integrasi WA real nanti

**Acceptance**:
- Akses page → tampil notification "Coming Soon" + redirect back
- Navigation badge `Soon` muncul
- Tidak ada implementasi WA service / job / migrate kolom `notif_presensi_aktif` (semua ditunda)

**Catatan**: Phase ini ringan, bisa digabung dgn Phase 4 kalau mau.


---

## 11. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| **Hardware/network down** saat jam masuk | Siswa tidak bisa tap, antrian panjang | (1) Device firmware harus punya local queue + retry. (2) Page Monitor Gerbang tampil status koneksi device, alert kalau down. (3) Petugas piket bisa input manual cepat via `MonitorGerbang` action |
| **Race condition** dua tap bersamaan (dua reader) | Duplikat record / status inconsistent | UNIQUE `(siswa_id, tanggal)` di DB + transaction `lockForUpdate` saat update presensi |
| **Token API bocor** | Pihak luar bisa fake scan | (1) Token disimpan hashed. (2) Rotate token via "Regenerate Token" action. (3) Rate limit per device-IP. (4) Audit log scan_logs membantu forensic |
| **Clock drift device** | `scanned_at` tidak akurat | Server pakai `now()` sebagai fallback kalau `scanned_at` skewed > X menit. Log keduanya |
| **Wali kelas lihat data anak kelas lain** | Privacy issue | Test scoping eksplisit di `PresensiHarianResourceTest` (section 9.3) |
| **Spam WA notif** kalau wali punya banyak anak | Wali blokir nomor sekolah | Rate limiter per nomor (section 7.3) |
| **Volume `rfid_scan_logs` membengkak** | Performa query lambat | (1) Index sudah disiapkan. (2) Job archival tahunan (move ke `rfid_scan_logs_archive`) — phase 6 nanti kalau perlu |
| **Siswa pinjam kartu temannya** | Data presensi salah | Bukan masalah aplikasi — tapi log `scan_logs` bisa membantu deteksi (mis. siswa A foto-nya muncul tapi piket lihat anak lain) |


---

## 12. File Inventory (Lengkap)

### 12.1 File Baru

**Migrations** (`database/migrations/`):
- `2026_05_24_xxxxxx_create_rfid_devices_table.php`
- `2026_05_24_xxxxxx_create_kartu_rfids_table.php`
- `2026_05_24_xxxxxx_create_presensi_harians_table.php`
- `2026_05_24_xxxxxx_create_rfid_scan_logs_table.php`
- `2026_05_24_xxxxxx_add_rfid_config_to_sekolahs_table.php`

**Models** (`app/Models/`):
- `PresensiHarian.php`
- `KartuRfid.php`
- `RfidDevice.php`
- `RfidScanLog.php`

**Factories** (`database/factories/`):
- `PresensiHarianFactory.php`
- `KartuRfidFactory.php`
- `RfidDeviceFactory.php`
- `RfidScanLogFactory.php`

**Seeders** (`database/seeders/`):
- `PresensiHarianSeeder.php`
- `KartuRfidSeeder.php`
- `RfidDeviceSeeder.php`

**Policies** (`app/Policies/`):
- `PresensiHarianPolicy.php`
- `KartuRfidPolicy.php`
- `RfidDevicePolicy.php`
- `RfidScanLogPolicy.php`

**Filament Resources** (`app/Filament/Resources/`):
- `PresensiHarians/PresensiHarianResource.php` + Pages + Schemas + Tables
- `KartuRfids/KartuRfidResource.php` + Pages + Schemas + Tables
- `RfidDevices/RfidDeviceResource.php` + Pages + Schemas + Tables
- `RfidScanLogs/RfidScanLogResource.php` + Pages (List + View only)

**Filament Pages** (`app/Filament/Pages/`):
- `MonitorGerbang.php` + view

**Filament Widgets** (`app/Filament/Widgets/`):
- `PresensiHariIniWidget.php`
- `PresensiPerJamWidget.php`

**API Layer**:
- `app/Http/Controllers/Api/RfidScanController.php`
- `app/Http/Requests/RfidScanRequest.php`
- `app/Http/Middleware/AuthenticateRfidDevice.php`
- `app/Http/Resources/RfidScanResource.php`
- `app/Services/Rfid/PresensiScanService.php`
- `routes/api.php`

**Jobs** (`app/Jobs/`):
- `KirimNotifPresensiMasuk.php` (Phase 5)

**Tests**:
- `tests/Feature/PresensiHarianResourceTest.php`
- `tests/Feature/KartuRfidResourceTest.php`
- `tests/Feature/RfidDeviceResourceTest.php`
- `tests/Feature/RfidScanLogResourceTest.php`
- `tests/Feature/Api/RfidScanApiTest.php`
- `tests/Unit/PresensiScanServiceTest.php`
- `tests/Browser/MonitorGerbangTest.php` (optional)

### 12.2 File Diubah

- `app/Models/Siswa.php` — tambah relasi `presensiHarians()`, `kartuRfids()`, `kartuRfidAktif()`
- `app/Models/Sekolah.php` — tambah `$fillable` & `casts()` untuk kolom config baru
- `app/Filament/Resources/Sekolahs/Schemas/SekolahForm.php` — tambah section "Konfigurasi Presensi RFID"
- `database/seeders/RoleSeeder.php` — tambah resource baru di `$allResources`, tambah permission di tiap role method
- `database/seeders/DatabaseSeeder.php` — tambah seeder baru
- `bootstrap/app.php` — register `routes/api.php`


---

## 13. Konfirmasi User (RESOLVED)

Semua pertanyaan sudah dijawab user pada 2026-05-24:

| # | Pertanyaan | Jawaban |
|---|---|---|
| 1 | Hardware reader | **ESP32 + RC522** |
| 2 | Format UID | **`04A1B2C3`** (hex uppercase, tanpa separator) |
| 3 | Provider WhatsApp existing | **Belum ada implementasi nyata** — `KirimNotifGaji` & `KirimTagihan` hanya placeholder page "Coming Soon". Notif presensi ikut pattern yang sama (placeholder dulu) |
| 4 | Default jam masuk | **`07:00`** ✓ |
| 5 | Phase 5 (WA notif) | **Coming Soon** — buat placeholder page saja, tidak implement WA sending sungguhan |
| 6 | Browser test | **Skip browser test** — cukup Pest feature/unit test |

### Implikasi terhadap Plan

- **Section 7 (WhatsApp)**: tidak implement `WhatsappService` & job dispatching. Cukup buat placeholder page `KirimNotifPresensi.php` yang follow pattern [KirimNotifGaji.php](file:///Users/prasa/Project/Laravel/si-sekolah/app/Filament/Pages/KirimNotifGaji.php).
- **Section 2.5 (sekolahs)**: kolom `notif_presensi_aktif` **TIDAK ditambahkan** (no real consumer yet — YAGNI).
- **Section 4 (API)**: `PresensiScanService` tidak dispatch job WA notif. Tinggalkan TODO comment untuk phase berikutnya.
- **Section 9.4 (Browser test)**: dihapus.
- **Section 10 Phase 5**: jadi sangat ringan — cuma buat 1 placeholder page + update nav.

### Validasi Format UID

Pattern regex untuk validasi: `/^[0-9A-F]{8,20}$/`
- Min 4 byte → 8 hex char → `04A1B2C3`
- Max 10 byte → 20 hex char → `04A1B2C3D4E5F6071829`
- Hanya hex uppercase, tanpa separator

Normalisasi di model `KartuRfid::setUidAttribute()`: terima input apapun (lowercase, dengan/tanpa separator `:` atau `-`), strip semua separator, simpan dalam format kanonikal **uppercase tanpa separator** (`04A1B2C3`). Reject input yang tidak match regex setelah normalisasi.

Implementasi mutator:
```php
public function setUidAttribute(string $value): void
{
    $normalized = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $value));

    if (! preg_match('/^[0-9A-F]{8,20}$/', $normalized)) {
        throw new \InvalidArgumentException("UID tidak valid: {$value}");
    }

    $this->attributes['uid'] = $normalized;
}
```

---

## 14. Dependencies

### 14.1 Composer

**TIDAK** ada package baru. Semua kebutuhan sudah tersedia:
- `laravel/framework` — routing, validation, queue
- `spatie/laravel-permission` — RBAC (via Filament Shield)
- `spatie/laravel-activitylog` — audit trail
- `pestphp/pest` — testing

### 14.2 NPM

**TIDAK** ada package baru. Tailwind v4 + Filament built-in cukup.

### 14.3 Hardware (di sisi user)

- Reader RFID (rekomendasi: ESP32 + RC522 untuk fleksibilitas)
- Konfigurasi WiFi sekolah dengan akses ke Laravel server
- (Opsional) Display LCD + buzzer di reader untuk feedback siswa

---

## 15. Approval Checklist

Sebelum implementasi dimulai, user konfirmasi:

- [ ] Skema database (section 2) — nama tabel, kolom, enum, index
- [ ] Naming `presensi_harians` (bukan `absen_rfids`)
- [ ] Status enum konsisten dgn `Absensi` existing (`hadir, terlambat, izin, sakit, alpha`)
- [ ] Foreign key ke `siswas.id` (bukan ke `nis`)
- [ ] API HTTP + device token (section 4)
- [ ] Independent dari `absensis` per-pelajaran (no auto-link)
- [ ] Scope: siswa only, pegawai phase 2
- [ ] Konfigurasi presensi di table `sekolahs`
- [ ] Permission mapping (section 6) untuk 7 role
- [ ] Phase 5 (WA notif) — include / skip / tunggu investigate
- [ ] 5 phase implementation (section 10) — mulai dari Phase 1

---

**Setelah approve, eksekusi mulai dari Phase 1.** Plan ini bisa di-update kalau ada finding di tengah jalan (mis. Phase 2 reveal sesuatu yang mengharuskan tweak skema).
