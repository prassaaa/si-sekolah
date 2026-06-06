# Rencana Modul: Sarana & Prasarana (Sarpras)

**Tanggal:** 2026-06-06
**Status:** Rencana (belum dieksekusi) — menunggu persetujuan.
**Cakupan disepakati:** FULL Sarpras (Master Data, Inventaris, Peminjaman, Pemeliharaan, Pengadaan, Penghapusan, Laporan).
**Integrasi akuntansi:** **Decoupled** — nilai aset dicatat di Sarpras saja, tidak posting ke `jurnal_umums`. (Penyusutan/depresiasi **di luar cakupan**.)

> Modul ini mengikuti konvensi yang sudah ada: Filament v4 (resource auto-discovery), label Bahasa Indonesia, `casts()` method, `SoftDeletes` + `spatie/activitylog`, factory + seeder per model, policy via filament-shield, dan pelajaran dari audit (`BUGS.md`): mutasi stok/status **wajib** `DB::transaction` + `lockForUpdate`, uang pakai `decimal:2` + bc-math, form composite-unique divalidasi, kolom relasi di tabel di-`eager load`, factory mengisi semua kolom wajib.

---

## 1. Tujuan

Mengelola seluruh sarana & prasarana sekolah: pendataan aset/inventaris, lokasi penyimpanan, peminjaman, pemeliharaan/perbaikan, pengadaan baru, dan penghapusan aset rusak — lengkap dengan laporan kondisi & nilai.

---

## 2. Menu & Navigasi

**Navigation Group baru:** `Sarana & Prasarana`
(Disisipkan di sidebar setelah grup "Akademik"/sebelum "Keuangan" — `navigationSort` diatur per resource.)

```
Sarana & Prasarana
├── Master Data
│   ├── Kategori Sarana
│   └── Ruangan / Lokasi
├── Inventaris
│   └── Data Barang / Aset
├── Peminjaman
│   └── Peminjaman Sarana
├── Pemeliharaan
│   └── Pemeliharaan & Perbaikan
├── Pengadaan
│   └── Pengadaan / Pembelian
├── Penghapusan
│   └── Penghapusan Aset
└── Laporan Sarpras (Menu Induk)
    ├── Laporan Inventaris
    ├── Laporan Kondisi
    ├── Laporan Peminjaman
    └── Laporan Pemeliharaan
```

> Filament v4 mendukung sub-grup via `navigationParentItem` / cluster bila diinginkan; default cukup satu group + `navigationSort`.

---

## 3. Model Data (ERD ringkas)

```
SarprasKategori ──< SarprasBarang >── Ruangan ──> Pegawai (penanggung_jawab)
                          │
        ┌─────────────────┼─────────────────┬───────────────────┐
        │                 │                 │                   │
  SarprasPeminjaman  SarprasPemeliharaan  SarprasPenghapusan   (stok via Pengadaan)
        │                                                        │
   morphTo peminjam (Siswa|Pegawai)                    SarprasPengadaan ──< SarprasPengadaanItem >── SarprasKategori
        │
   belongsTo Pegawai (petugas)
```

### 3.1 Tabel & kolom

**`sarpras_kategoris`**
| kolom | tipe | catatan |
|-------|------|---------|
| id | bigId | |
| kode | string unique | mis. ELK, MBL, LAB |
| nama | string | |
| deskripsi | text null | |
| is_active | bool default true | |
| timestamps, softDeletes | | |

**`ruangans`** (master ruangan kaya — terpisah dari `kelas.ruangan` string yang lama, lihat §9)
| kolom | tipe | catatan |
|-------|------|---------|
| id | bigId | |
| kode | string unique | |
| nama | string | |
| jenis | enum | kelas, lab, kantor, gudang, perpustakaan, aula, lainnya |
| gedung | string null | |
| lantai | int null | |
| kapasitas | int null | |
| penanggung_jawab_id | FK pegawais null (nullOnDelete) | |
| keterangan | text null | |
| is_active | bool default true | |
| timestamps, softDeletes | | |

**`sarpras_barangs`** (aset/inventaris — inti)
| kolom | tipe | catatan |
|-------|------|---------|
| id | bigId | |
| kode_inventaris | string unique | |
| nama | string | |
| sarpras_kategori_id | FK | restrictOnDelete |
| ruangan_id | FK ruangans null | nullOnDelete |
| tipe | enum | `aset` (barang tahan lama) / `bahan` (habis pakai) — menentukan perlakuan stok |
| merk | string null | |
| spesifikasi | text null | |
| kondisi | enum | baik, rusak_ringan, rusak_berat |
| status | enum | tersedia, dipinjam, perbaikan, dihapus |
| sumber_dana | enum | bos, komite, yayasan, hibah, pribadi, lainnya |
| tahun_perolehan | year null | |
| harga_perolehan | decimal(15,2) default 0 | |
| jumlah | int default 1 | untuk `bahan`/barang banyak; `aset` umumnya 1 |
| satuan | string default 'unit' | |
| foto | string null | |
| keterangan | text null | |
| is_active | bool default true | |
| timestamps, softDeletes | | |

**`sarpras_peminjamans`**
| kolom | tipe | catatan |
|-------|------|---------|
| id | bigId | |
| nomor | string unique | auto `PJM-YYYYMM-NNNN` (numbering pakai txn+lock, hindari race spt SlipGaji) |
| sarpras_barang_id | FK | restrictOnDelete |
| peminjam_type / peminjam_id | nullableMorphs | Siswa atau Pegawai (pola sama `KartuRfid.owner`) |
| jumlah | int default 1 | |
| tanggal_pinjam | date | |
| tanggal_harus_kembali | date | |
| tanggal_kembali | date null | |
| kondisi_pinjam | enum | baik, rusak_ringan, rusak_berat |
| kondisi_kembali | enum null | |
| status | enum | dipinjam, dikembalikan, terlambat, hilang |
| petugas_id | FK pegawais null | |
| catatan | text null | |
| timestamps, softDeletes | | |
| unique | (sarpras_barang_id) parsial? tidak — validasi ketersediaan di service | |

**`sarpras_pemeliharaans`**
| kolom | tipe | catatan |
|-------|------|---------|
| id | bigId | |
| nomor | string unique | `PML-YYYYMM-NNNN` |
| sarpras_barang_id | FK | restrictOnDelete |
| jenis | enum | rutin, perbaikan, kalibrasi |
| tanggal | date | |
| tanggal_selesai | date null | |
| deskripsi_masalah | text | |
| tindakan | text null | |
| pelaksana | enum | internal, vendor |
| nama_vendor | string null | |
| biaya | decimal(15,2) default 0 | |
| kondisi_sebelum | enum null | |
| kondisi_sesudah | enum null | |
| status | enum | dijadwalkan, proses, selesai, batal |
| dicatat_oleh | FK users null | |
| timestamps, softDeletes | | |

**`sarpras_pengadaans`** (header)
| kolom | tipe | catatan |
|-------|------|---------|
| id | bigId | |
| nomor | string unique | `PGD-YYYYMM-NNNN` |
| tanggal | date | |
| sumber_dana | enum | (sama dgn barang) |
| penyedia | string null | supplier/toko |
| total_biaya | decimal(15,2) default 0 | dihitung dari items |
| status | enum | draft, disetujui, diterima, batal |
| keterangan | text null | |
| dibuat_oleh | FK users null | |
| timestamps, softDeletes | | |

**`sarpras_pengadaan_items`**
| kolom | tipe | catatan |
|-------|------|---------|
| id | bigId | |
| sarpras_pengadaan_id | FK | cascadeOnDelete |
| nama_barang | string | |
| sarpras_kategori_id | FK | restrictOnDelete |
| jumlah | int | |
| satuan | string default 'unit' | |
| harga_satuan | decimal(15,2) | |
| subtotal | decimal(15,2) | jumlah × harga_satuan (bc-math) |
| timestamps | | |

**`sarpras_penghapusans`**
| kolom | tipe | catatan |
|-------|------|---------|
| id | bigId | |
| nomor | string unique | `PHP-YYYYMM-NNNN` |
| sarpras_barang_id | FK | restrictOnDelete |
| tanggal | date | |
| alasan | enum | rusak_berat, hilang, usang, lainnya |
| jumlah | int default 1 | |
| nilai_sisa | decimal(15,2) default 0 | |
| metode | enum | dibuang, dijual, disumbangkan |
| disetujui_oleh | FK users null | |
| status | enum | diajukan, disetujui, ditolak |
| keterangan | text null | |
| timestamps, softDeletes | | |

---

## 4. Migrations (urutan dependensi)

1. `create_sarpras_kategoris_table`
2. `create_ruangans_table`
3. `create_sarpras_barangs_table`
4. `create_sarpras_peminjamans_table`
5. `create_sarpras_pemeliharaans_table`
6. `create_sarpras_pengadaans_table`
7. `create_sarpras_pengadaan_items_table`
8. `create_sarpras_penghapusans_table`

> Buat via `php artisan make:migration --no-interaction` agar timestamp urut. FK delete-rule: master → `restrictOnDelete` (cegah hapus kategori/barang yg dipakai), header→item `cascadeOnDelete`.

---

## 5. Models (`app/Models/`)

`SarprasKategori`, `Ruangan`, `SarprasBarang`, `SarprasPeminjaman`, `SarprasPemeliharaan`, `SarprasPengadaan`, `SarprasPengadaanItem`, `SarprasPenghapusan`.

Setiap model:
- `use HasFactory, LogsActivity, SoftDeletes;` (kecuali item: tanpa softDeletes).
- `casts()` method (date/decimal:2/int/bool/enum-string).
- Relasi ber-return-type (`BelongsTo`, `HasMany`, `MorphTo`).
- Scope umum (`scopeActive`, `scopeTersedia`, dst), accessor `*_info` (label+color) untuk badge Filament (pola sama `KartuRfid::getStatusInfoAttribute`).

**Logika bisnis (semua dalam `DB::transaction` + `lockForUpdate`, bc-math untuk uang):**
- `SarprasPeminjaman`: saat `dipinjam` → set `barang.status='dipinjam'` (atau kurangi stok untuk `bahan`); saat dikembalikan → `tersedia` + simpan `kondisi_kembali` ke `barang.kondisi`; helper `kembalikan()`. Validasi: barang harus `tersedia` & `jumlah` cukup (cegah pinjam ganda — pelajaran race-condition dari audit).
- `SarprasPemeliharaan`: `proses` → `barang.status='perbaikan'`; `selesai` → `tersedia` + `barang.kondisi=kondisi_sesudah`.
- `SarprasPengadaan`: `total_biaya` dihitung dari items (bc); saat `diterima` → upsert `sarpras_barangs` (idempoten, tandai sudah-diterima agar tak dobel intake).
- `SarprasPenghapusan`: `disetujui` → `barang.status='dihapus'` (+ soft-delete/`is_active=false`); idempoten.
- Auto-numbering `nomor` (semua header): `DB::transaction`+`lockForUpdate` atau retry unique — **bukan** pola `max()` rawan race (lihat SlipGaji di BUGS.md H17).

---

## 6. Filament (`app/Filament/`)

### Resources (`Resources/`) — auto-discovered, group `Sarana & Prasarana`
| Resource | Model | navigationSort | Icon (Heroicon) |
|----------|-------|----------------|-----------------|
| `SarprasKategoriResource` | SarprasKategori | 1 | OutlinedTag |
| `RuanganResource` | Ruangan | 2 | OutlinedBuildingOffice2 |
| `SarprasBarangResource` | SarprasBarang | 3 | OutlinedCube |
| `SarprasPeminjamanResource` | SarprasPeminjaman | 4 | OutlinedArrowsRightLeft |
| `SarprasPemeliharaanResource` | SarprasPemeliharaan | 5 | OutlinedWrenchScrewdriver |
| `SarprasPengadaanResource` | SarprasPengadaan | 6 | OutlinedShoppingCart |
| `SarprasPenghapusanResource` | SarprasPenghapusan | 7 | OutlinedTrash |

Tiap resource ikut struktur folder yang ada: `XxxResource.php` + `Schemas/XxxForm.php` + `Schemas/XxxInfolist.php` + `Tables/XxxsTable.php` + `Pages/`. Pengadaan items via **Repeater** di form atau **RelationManager**.

Penerapan pelajaran audit di form/table:
- Composite-unique → validasi `->unique(...)` di form (kode_inventaris, kode kategori/ruangan, nomor).
- Field uang `->minValue(0)`, `numeric`.
- Tabel dengan kolom relasi (kategori.nama, ruangan.nama, peminjam) → `getEloquentQuery()->with([...])` (cegah N+1).
- `peminjam_type→peminjam_id` reactive (pola `KartuRfidForm`).

### Pages (`Pages/`) — Laporan
`LaporanInventaris`, `LaporanKondisiSarpras`, `LaporanPeminjaman`, `LaporanPemeliharaan` (filter: kategori, ruangan, kondisi, rentang tanggal). Agregasi **di SQL** (bukan `->get()->groupBy()` — pelajaran H16).

### Widgets (`Widgets/`)
- `SarprasOverviewWidget` — total aset, total nilai (sum harga_perolehan), breakdown kondisi.
- `PeminjamanAktifWidget` — sedang dipinjam / terlambat.
- `BarangPerluPerbaikanWidget` — kondisi rusak / status perbaikan.

---

## 7. RBAC (filament-shield)

- Resource baru otomatis terdeteksi shield; jalankan `php artisan shield:generate` → generate 8 policy baru (`SarprasKategoriPolicy`, dst).
- Tambah role **`petugas_sarpras`** di `RoleSeeder` (CRUD penuh modul Sarpras, read laporan). Beri `super_admin` semua; `tata_usaha`/`admin` akses penuh; `guru`/`wali_kelas` read + ajukan peminjaman (opsional).
- Update `RoleSeeder` daftar resource + permission.

---

## 8. Factory, Seeder, Test

- **Factory** untuk 8 model — isi **semua kolom wajib** (pelajaran: BuktiTransferFactory stub bikin test gagal). State berguna: `tersedia()/dipinjam()/rusak()`, `aset()/bahan()`.
- **Seeder** realistis + daftarkan di `DatabaseSeeder` urut dependensi (kategori → ruangan → barang → transaksi). Update `DatabaseSeeder::run()`.
- **Test (Pest, `tests/Feature` & `tests/Unit`)**:
  - Render list/create/edit tiap resource.
  - Lifecycle: peminjaman→barang jadi `dipinjam`→pengembalian→`tersedia`; pinjam barang tak-tersedia ditolak; pengadaan `diterima`→stok bertambah (idempoten); penghapusan `disetujui`→barang `dihapus`.
  - Numbering `nomor` unik di bawah konkurensi.
  - Validasi form composite-unique & nilai negatif.

---

## 9. Keputusan & catatan

| # | Hal | Keputusan |
|---|-----|-----------|
| 1 | `ruangans` vs `kelas.ruangan` (string lama) | Buat master `ruangans` baru. **Tidak** mengubah `kelas` sekarang. Opsi masa depan: tambah `kelas.ruangan_id` FK + migrasi data (di luar cakupan, dicatat). |
| 2 | Aset unit-tunggal vs jumlah | Kolom `tipe` (`aset`/`bahan`). `aset` = 1 baris/unit dgn `kode_inventaris` unik; `bahan` = pakai `jumlah`. Peminjaman bahan mengurangi stok, aset mengubah status. |
| 3 | Peminjam | Polymorphic `Siswa`/`Pegawai` (konsisten `KartuRfid.owner`). |
| 4 | Akuntansi | **Decoupled** (sesuai pilihan). Tidak ada observer ke `jurnal_umums`, tidak ada penyusutan. Bisa ditambah nanti tanpa ubah skema inti. |
| 5 | Denda telat peminjaman | **Tidak** di v1 (status `terlambat` saja). Bisa ditambah field `denda` nanti. |

---

## 10. Tahapan implementasi (saran)

| Fase | Isi | Output |
|------|-----|--------|
| **F1 — Master Data** | Migrasi+model+resource: Kategori, Ruangan, Barang + factory/seeder/test | Inventaris bisa dikelola |
| **F2 — Peminjaman** | Model+resource+lifecycle+test | Pinjam/kembali jalan |
| **F3 — Pemeliharaan** | Model+resource+lifecycle+test | Servis tercatat |
| **F4 — Pengadaan** | Header+items+intake stok+test | Pembelian → stok |
| **F5 — Penghapusan** | Model+resource+approval+test | Write-off aset |
| **F6 — Laporan + Widget** | 4 laporan + 3 widget | Dashboard & rekap |
| **F7 — RBAC + polish** | shield:generate, role seeder, pint, full test | Siap pakai |

Estimasi: 8 migrasi, 8 model, 7 resource, 4 page laporan, 3 widget, 8 factory, 8 seeder, 8 policy, + test. Tiap fase: `vendor/bin/pint --dirty` + `php artisan test --compact --filter=Sarpras|Ruangan|Peminjaman|...`.

---

## 11. Langkah berikut

Konfirmasi rencana ini (atau koreksi keputusan di §9). Setelah disetujui, eksekusi mulai **F1** — bisa paralel per fase via ultrawork dengan grup file-disjoint seperti pada perbaikan bug.
