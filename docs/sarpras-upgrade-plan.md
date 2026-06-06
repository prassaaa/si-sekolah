# Rencana Upgrade Modul Sarpras

**Tanggal:** 2026-06-06
**Status:** Rencana (belum dieksekusi) — menunggu persetujuan + isian keputusan bisnis.
**Prasyarat:** Modul Sarpras dasar sudah selesai & lulus test (lihat `docs/sarpras-plan.md`). Plan ini menaikkan 3 keputusan yang tadinya ditunda.

> Mengikuti konvensi & pelajaran audit (`BUGS.md`): mutasi stok/uang via `DB::transaction`+`lockForUpdate` + bc-math; migrasi ubah-kolom restate semua atribut; form composite-unique divalidasi; eager-load relasi; factory isi kolom wajib; tiap perubahan disertai test Pest.

## Lingkup

| Point (dari `sarpras-plan.md` §9) | Status sekarang | Upgrade |
|---|---|---|
| #1 Ruangan ↔ Kelas | `ruangans` master terpisah, `kelas.ruangan` string | Hubungkan via FK + migrasi data |
| #2 tipe aset/bahan | **Final** | — (tak diubah, sudah optimal) |
| #3 peminjam polymorphic | **Final** | — (tak diubah, sudah optimal) |
| #4 Akuntansi | Decoupled | Penyusutan + posting jurnal |
| #5 Denda telat | Tak ada | Hitung & catat denda |

> #2 & #3 sengaja tidak diupgrade: opsi terpilih sudah terbaik; alternatifnya inferior (2 tabel terpisah / dua kolom nullable / teks bebas).

---

## ⚠️ Keputusan bisnis yang WAJIB diisi sebelum eksekusi

Default di bawah dipakai bila tak ada koreksi.

**Untuk #4 (akuntansi):**
| Hal | Default usulan | Perlu konfirmasi |
|---|---|---|
| Akun Aset Tetap (debit saat pengadaan) | Akun tipe `aset`, kategori `tetap`, kode mis. `1-2001` | kode akun aktual di COA |
| Akun lawan pengadaan | Kas/Bank (`aset` lancar) atau Hutang | tunai vs kredit |
| Akun Beban Penyusutan | tipe `beban`, kode mis. `5-2001` | kode aktual |
| Akun Akumulasi Penyusutan | kontra-aset, kode mis. `1-2901` | kode aktual |
| Metode penyusutan | Garis lurus (straight-line) | atau saldo menurun |
| Default umur ekonomis | per kategori (elektronik 4th, meubelair 8th, kendaraan 8th, bangunan 20th) | nilai per kategori |
| Frekuensi posting penyusutan | Bulanan (command terjadwal) | bulanan/tahunan |

**Untuk #5 (denda):**
| Hal | Default usulan | Perlu konfirmasi |
|---|---|---|
| Tarif denda | Rp 1.000 / hari / unit | nominal |
| Batas maksimal denda | 50% × harga_perolehan | ada/tidak |
| Kena ke siapa | Siswa & Pegawai | siswa saja? |
| Sumber tarif | kolom di `sekolahs` (`tarif_denda_sarpras_per_hari`) | global vs per-kategori |
| Integrasi tagihan | Tidak (catat di peminjaman saja) | tagih via Pembayaran? |

---

## #1 — Integrasi Ruangan ↔ Kelas

**Tujuan:** `kelas.ruangan` (string bebas) → `kelas.ruangan_id` (FK ke `ruangans`), satu sumber data ruangan.

**Langkah:**
1. Migrasi A: `add_ruangan_id_to_kelas_table` — `$table->foreignId('ruangan_id')->nullable()->after('ruangan')->constrained('ruangans')->nullOnDelete();`
2. Migrasi B (data): untuk tiap `kelas` dgn `ruangan` string non-kosong → `Ruangan::firstOrCreate(['nama'=>..],['kode'=>generate,'jenis'=>'kelas','is_active'=>true])`, set `kelas.ruangan_id`. Idempoten.
3. Model: `Kelas` `belongsTo Ruangan`; `Ruangan` `hasMany Kelas`.
4. `KelasForm`: TextInput `ruangan` → `Select::make('ruangan_id')->relationship('ruangan','nama')->searchable()->preload()` (+ createOptionForm opsional).
5. `KelasesTable` / Infolist: kolom `ruangan.nama`; eager-load `with('ruangan')`.
6. Migrasi C (cleanup, **setelah** data & UI aman): drop kolom string `ruangan`.
7. Update `KelasSeeder`/`KelasFactory` agar pakai `ruangan_id` (atau biarkan nullable).

**Risiko:** sedang — sentuh modul Akademik (Kelas dipakai Siswa, Jadwal, dll). Mitigasi: migrasi data idempoten, jalankan migrasi C terpisah setelah verifikasi, test `KelasResource` + relasi.
**Test:** Kelas create/edit pakai ruangan_id; migrasi data memetakan string lama; `kelas.ruangan` lama ter-drop tanpa kehilangan info.
**Effort:** ~½ wave (3 migrasi + edit Kelas model/form/table/seeder/factory/test).

---

## #4 — Integrasi Akuntansi (2 sub-fase)

### Fase 4a — Penyusutan (TANPA jurnal) — risiko rendah
**Tujuan:** hitung depresiasi aset untuk laporan; tak menyentuh `jurnal_umums`.

**Langkah:**
1. Migrasi: tambah ke `sarpras_barangs` — `metode_susut` enum(garis_lurus, saldo_menurun, tanpa) default tanpa, `umur_ekonomis_bulan` int null, `nilai_residu` decimal(15,2) default 0, `tanggal_perolehan` date null (lebih presisi dari `tahun_perolehan`).
2. Service `app/Services/Sarpras/PenyusutanService.php`: hitung penyusutan/bulan & nilai buku per barang (bc-math). Garis lurus = `(harga_perolehan − nilai_residu) / umur_ekonomis_bulan`.
3. Default umur per kategori (lihat tabel keputusan).
4. Page `LaporanPenyusutan` (group Sarpras / Laporan): nilai perolehan, akumulasi, nilai buku per barang/kategori — agregasi SQL.
5. Widget opsional: total nilai buku aset.

**Risiko:** rendah (read-only kalkulasi). **Effort:** ~½ wave.

### Fase 4b — Posting Jurnal (double-entry) — risiko tinggi
**Tujuan:** pengadaan & penyusutan otomatis masuk `jurnal_umums`.
**⚠️ Prasyarat keras:** beresin dulu audit **C6** (`KasMasuk`/`KasKeluar` belum posting ke ledger) supaya pola posting kas konsisten — jangan buat dua pola berbeda.

**Langkah:**
1. Tentukan akun COA (tabel keputusan). Tambah helper resolusi akun (by kode/konvensi) dengan fallback aman: bila akun tak ketemu → **skip posting + log warning**, jangan tebak (pelajaran C6).
2. Observer `SarprasPengadaan::terima()` → post jurnal seimbang: debit Aset Tetap, kredit Kas/Hutang. Idempoten via `jenis_referensi`+`referensi_id` ke baris pengadaan; reverse saat batal/hapus.
3. Command terjadwal `sarpras:susut-bulanan` → untuk tiap aset aktif post: debit Beban Penyusutan, kredit Akumulasi Penyusutan (idempoten per periode/aset, guard dobel-posting).
4. Neraca/LabaRugi otomatis ikut karena baca `jurnal_umums` (sudah diperbaiki di audit H11–H14).
5. Backfill historis (opsional, hati-hati): script posting pengadaan & penyusutan lampau — jalankan manual + verifikasi neraca balance.

**Risiko:** tinggi — keputusan akuntansi + integritas ledger + backfill. **Effort:** ~1 wave + keputusan COA.

---

## #5 — Denda Telat Peminjaman

**Tujuan:** hitung & catat denda saat pengembalian telat.

**Langkah:**
1. Migrasi A: `sarpras_peminjamans` tambah `denda` decimal(15,2) default 0, `hari_telat` int default 0.
2. Migrasi B: `sekolahs` tambah `tarif_denda_sarpras_per_hari` decimal default 0 (+ `maks_denda_persen` opsional).
3. Model `SarprasPeminjaman::kembalikan()`: hitung `hari_telat = max(0, tanggal_kembali − tanggal_harus_kembali)`, `denda = min(maks, hari_telat × tarif × jumlah)` (bc-math), simpan — dalam txn+lock yang sudah ada.
4. Form/Table: tampilkan `denda`, `hari_telat`; aksi Kembalikan tampilkan nominal sebelum konfirmasi.
5. (Opsional, fase lanjut) integrasi tagihan: buat record denda yang bisa ditagih via Pembayaran — **butuh keputusan**, default TIDAK.
6. SekolahResource form: field tarif denda.

**Risiko:** rendah (tanpa integrasi pembayaran). **Effort:** ~½ wave (2 migrasi + model/form/test + setting).

---

## Urutan eksekusi yang disarankan

| Urut | Item | Alasan |
|---|---|---|
| 1 | **#1** Ruangan↔Kelas | Murni teknis, no keputusan bisnis, rapikan data |
| 2 | **#5** Denda | Kecil, hanya butuh tarif |
| 3 | **#4a** Penyusutan | Laporan, no jurnal, risiko rendah |
| 4 | **#4b** Posting jurnal | Terakhir; idealnya setelah audit C6 beres; butuh COA |

Tiap fase: implementasi → `vendor/bin/pint --dirty` → test terfilter → full suite. Bisa paralel via ultrawork grup file-disjoint seperti sebelumnya.

---

## Langkah berikut
Konfirmasi plan + isi tabel keputusan bisnis (§ "Keputusan WAJIB"). Setelah itu sebut fase mana dieksekusi (atau "semua urut"). Untuk #4b, konfirmasi apakah audit C6 dikerjakan dulu.
