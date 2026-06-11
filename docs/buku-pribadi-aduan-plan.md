# Rencana Fitur Lomba: Buku Pribadi Siswa + Aduan Siswa

**Tanggal:** 2026-06-11
**Status:** Rencana (belum dieksekusi) — menunggu persetujuan.
**Konteks:** Fitur tambahan formal untuk lomba: *Konseling → Buku pribadi siswa → Riwayat kriminal siswa → bisa di-eksport dan ditunjukkan ke walimurid*, plus *Aduan siswa*.

> Mengikuti konvensi codebase: struktur resource Filament v4 (Resource + Schemas/Form + Infolist + Tables + 4 Pages), accessor `*_info` untuk badge, `dicatat_oleh` FK `users`, service per-domain di `app/Services/`, shield `{Action}:{Resource}`, tiap perubahan disertai test Pest.

## Keputusan terkunci (hasil diskusi 2026-06-11)

| Hal | Keputusan |
|---|---|
| Portal/login walimurid | **TIDAK** — buku pribadi di-export PDF oleh staff, dicetak/ditunjukkan langsung ke walimurid |
| Kanal aduan | **Dicatat staff** (TU/guru BK) dari aduan lisan/tertulis — tanpa form publik |
| Format export | **PDF saja** (dompdf) — tanpa Excel/ExportAction |
| "Riwayat kriminal siswa" | Dipenuhi modul **Pelanggaran** existing (kategori ringan/sedang/berat + poin), ditampilkan sebagai bagian Buku Pribadi |
| Commit | **Jangan commit** tanpa perintah eksplisit |

**Out of scope eksplisit:** panel/guard walimurid, tabel `walimurids`/`siswa_walimurid`, Excel export, form aduan publik, notifikasi.

---

## ⚠️ Prasyarat: bersihkan sisa sesi sebelumnya (LOKAL saja, prod tidak pernah kena)

Implementasi sebelumnya sudah di-revert di level file, tapi menyisakan:

1. **DB lokal** (belum terverifikasi — MySQL mati saat dicek): kemungkinan masih ada tabel `walimurids`, `siswa_walimurid`, `aduans` (skema lama) + 3 entri menggantung di tabel `migrations`. Cleanup:
   ```sql
   DROP TABLE IF EXISTS siswa_walimurid;
   DROP TABLE IF EXISTS aduans;
   DROP TABLE IF EXISTS walimurids;
   DELETE FROM migrations WHERE migration LIKE '%walimurid%' OR migration LIKE '%aduan%';
   ```
2. **Folder stub kosong**: hapus `app/Filament/Resources/Walimurids/`; `app/Filament/Resources/Aduans/` dipakai ulang oleh resource baru.
3. **vendor/**: masih berisi paket sisa install kemarin; `composer install` menyinkronkan balik ke lock. dompdf di-install ulang bersih via langkah #0.

Migrasi baru memakai **nama file baru** (timestamp hari ini) — tidak menabrak entri lama. Migrasi sengaja **tanpa** guard `hasTable` agar gagal keras bila cleanup terlewat (lebih aman daripada diam-diam memakai skema lama).

---

## #0 — Dependency: dompdf (satu-satunya paket baru)

**Pelajaran insiden kemarin:** install di PHP lokal 8.5 menyeret symfony 8.x (butuh PHP ≥8.4.1) → fatal `platform_check.php`. Prod = PHP 8.2.

**Langkah:**
1. Pin resolusi: `composer config platform.php 8.3.0`
   (8.2 tidak bisa — `pestphp/pest ^4.3` dev-dep butuh ^8.3; pin 8.3 tetap mencegah symfony 8.x masuk lock.)
2. `composer require barryvdh/laravel-dompdf:^3.1` — partial update saja, **jangan** `composer update` penuh.
3. Verifikasi: `php artisan about` boots; `composer check-platform-reqs`.
4. Catatan prod: deploy = `composer install --no-dev` di PHP 8.2. Chain dompdf (dompdf/dompdf, php-font-lib, php-svg-lib, masterminds/html5) semua support ≥8.1; Laravel 12 + symfony 7.x support 8.2. Aman.

**Risiko:** rendah-sedang (dependency churn). Mitigasi: partial require + platform pin; bila lock berubah liar → stop & review.

---

## #1 — Aduan Siswa

**Tujuan:** pencatatan & penanganan aduan (dari siswa/walimurid) oleh staff, dengan workflow status.

### Migrasi `create_aduans_table`
| Kolom | Tipe | Ket |
|---|---|---|
| siswa_id | FK `siswas`, nullable, nullOnDelete | aduan bisa terkait siswa tertentu atau umum |
| pelapor | string | nama pelapor |
| hubungan_pelapor | enum(siswa, ayah, ibu, wali, lainnya) default lainnya | |
| kontak_pelapor | string nullable | HP/email |
| tanggal_aduan | date | |
| kategori | enum(akademik, fasilitas, perlakuan, keuangan, lainnya) default lainnya | |
| judul | string | |
| isi | text | |
| lampiran | string nullable | FileUpload dir `aduan-lampiran` |
| status | enum(baru, diproses, selesai, ditolak) default baru | |
| ditangani_oleh | FK `pegawais`, nullable, nullOnDelete | |
| tanggapan | text nullable | |
| tanggal_tanggapan | timestamp nullable | |
| dicatat_oleh | FK `users`, nullable, nullOnDelete | konvensi existing |
| timestamps, softDeletes | | index: status, kategori, tanggal_aduan, siswa_id |

### Model `Aduan`
- Traits: `HasFactory`, `LogsActivity`, `SoftDeletes`; casts `tanggal_aduan` date, `tanggal_tanggapan` datetime.
- Relasi: `siswa()`, `penangan()` (Pegawai via ditangani_oleh), `pencatat()` (User via dicatat_oleh).
- Accessor: `getStatusInfoAttribute()`, `getKategoriInfoAttribute()` (pola house `['label','color']`).
- Method `tanggapi(string $tanggapan, int $pegawaiId, string $status = 'selesai'): void`.
- `Siswa`: tambah relasi `aduans(): HasMany`.
- `AduanFactory` (+ states `diproses()`, `selesai()`) dan `AduanSeeder` (8–12 data demo untuk lomba).

### Filament `AduanResource` (panel staff)
- Group `Kesiswaan`, sort 7, icon megaphone; badge = count `status='baru'` (danger) — pola PelanggaranResource.
- **Form** 3 section: *Pelapor* (pelapor, hubungan_pelapor, kontak_pelapor, siswa_id select searchable+preload, tanggal_aduan; dicatat_oleh auto = user login), *Isi Aduan* (kategori, judul, isi, lampiran), *Penanganan* (status, ditangani_oleh, tanggapan, tanggal_tanggapan) — disembunyikan saat create.
- **Table:** judul (limit), siswa.nama (placeholder `-`), pelapor + hubungan (badge), kategori (badge), status (badge), tanggal_aduan, penangan.nama (toggleable). Filter: kategori, status, trashed. Aksi: View, Edit, + aksi cepat **"Tanggapi"** (modal status+tanggapan → isi penangan dari user login, tanggal_tanggapan=now) tampil bila status masih baru/diproses.
- **Infolist** mirror form + Informasi Sistem (collapsed).

### RBAC
RoleSeeder: tambah `Aduan` ke daftar resource → `fullCrud`: `tata_usaha`, `guru_bk`, `admin`; `super_admin` otomatis. Jalankan ulang seeder/shield.

### Test `AduanResourceTest`
List ok; create + validasi (judul/isi/kategori wajib); edit; view; aksi Tanggapi mengisi penangan+tanggal+status; user tanpa izin → forbidden; soft delete + restore.

**Risiko:** rendah — modul baru terisolasi. **Effort:** ~½ wave.

---

## #2 — Buku Pribadi Siswa (export PDF)

**Tujuan:** satu dokumen PDF formal per siswa (kop sekolah + TTD kepsek) berisi identitas + seluruh riwayat, dicetak staff untuk ditunjukkan/diserahkan ke walimurid.

### Service `app/Services/Kesiswaan/BukuPribadiService.php`
(Folder domain baru `Kesiswaan/` — konsisten dengan `Accounting/`, `Sarpras/`, `Rfid/`.)
- `data(Siswa $siswa): array` — loadMissing `kelas.ruangan`, `konselings.konselor`, `konselings.semester`, `pelanggarans.semester`, `prestasis.semester`, `tahfidzs.semester`; rekap presensi `selectRaw('status, COUNT(*)') groupBy status`; `Sekolah::query()->first()`; `total_poin` = sum poin pelanggaran.
- `pdf(Siswa $siswa)` — `Pdf::loadView('exports.buku-pribadi', ...)->setPaper('a4', 'portrait')`.
- `filename(Siswa $siswa)` — `buku-pribadi-{nis}-{slug-nama}.pdf`.

### View `resources/views/exports/buku-pribadi.blade.php`
1. **Kop:** logo (path lokal `storage_path`, dompdf tidak bisa URL; fallback tanpa logo bila file tak ada), nama sekolah, alamat, NPSN, garis dobel.
2. Judul **BUKU PRIBADI SISWA**.
3. **A. Identitas Siswa** — biodata + ringkasan ayah/ibu/wali (nama, pekerjaan, telepon).
4. **B. Rekap Presensi** — hadir/izin/sakit/alpha (+terlambat).
5. **C. Riwayat Pelanggaran** *(inti "riwayat kriminal")* — tanggal, jenis, kategori, poin, status + **TOTAL POIN**.
6. **D. Riwayat Konseling** — **ringkas**: tanggal, jenis, kategori, konselor, status. ⚠️ Privasi: `permasalahan`/`hasil_konseling`/`rekomendasi` TIDAK dicetak (dokumen diserahkan ke walimurid; isi sesi konseling rahasia BK). Bisa diubah bila sekolah minta.
7. **E. Prestasi** — tanggal, nama, tingkat, peringkat.
8. **F. Tahfidz** — tanggal, surah, ayat, juz, nilai, status.
9. **TTD:** tempat + tanggal cetak, Kepala Sekolah (nama + NIP dari `sekolahs`).
- Font DejaVu (default dompdf, aman untuk teks latin Indonesia).

### Integrasi UI (edit file existing minimal)
- `ViewSiswa` header action + `SiswasTable` row action **"Cetak Buku Pribadi"** (icon printer) → `response()->streamDownload(...)`.
- Gate: cukup permission `View:Siswa` existing — tanpa permission baru.

### Test `BukuPribadiTest`
Service `data()` (rekap presensi & total_poin benar); `pdf()` output diawali `%PDF`; blade render aman untuk siswa minim data (null-safe); action tampil & ter-download untuk user berizin.

**Risiko:** rendah. **Effort:** ~½–1 wave.

---

## Urutan eksekusi yang disarankan

| Urut | Item | Ket |
|---|---|---|
| 1 | Prasyarat cleanup lokal | manual, ±5 menit (MySQL harus nyala) |
| 2 | **#0** dompdf | kecil, foreground |
| 3 | **#1** Aduan + **#2** Buku Pribadi | bisa paralel (file-disjoint) |
| 4 | Finalize | RoleSeeder + shield, `vendor/bin/pint --dirty`, test terfilter, full suite |

Tanpa commit — menunggu perintah eksplisit.

## Risiko & mitigasi

| Risiko | Mitigasi |
|---|---|
| Dependency churn dompdf (insiden symfony 8 kemarin) | platform pin 8.3.0 + partial require + `check-platform-reqs` |
| Sisa DB lokal dari sesi yang di-revert | cleanup pre-step + nama file migrasi baru + fail keras bila tabel lama tersisa |
| Privasi isi konseling pada dokumen untuk walimurid | default ringkas (tanpa isi sesi) |
| Logo di PDF | pakai path lokal + cek `file_exists`, fallback tanpa logo |
| Dampak ke prod | hanya +1 tabel `aduans` + 1 paket composer; nol perubahan tabel existing |

## Langkah berikut
Review plan ini; koreksi field/keputusan bila perlu; lalu beri perintah eksekusi (mis. "GASS" atau sebut fase tertentu).
