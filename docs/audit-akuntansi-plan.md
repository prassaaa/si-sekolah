# Hasil Audit Penuh: Modul Akuntansi & Laporan Keuangan

**Tanggal:** 2026-06-12 (keputusan bisnis dikunci 2026-06-13)
**Status:** Hasil audit + rencana perbaikan — **keputusan bisnis FINAL, siap eksekusi per wave**.
**Metode:** Audit multi-agen 4 fase (pemetaan 6 subdomain → audit 7 dimensi → verifikasi adversarial — temuan kritis/tinggi divoting 3 verifikator independen → critic kelengkapan). 207 agen, 99 temuan terkonfirmasi, 0 ditolak.

**Cakupan:** COA/Akun, JurnalUmum, SaldoAwal, KasMasuk/KasKeluar, BuktiTransfer, Tagihan/Pembayaran/Paket/PosBayar/UnitPos, TabunganSiswa, SettingGaji/SlipGaji/Pajak, 16 halaman laporan, widget keuangan, RBAC, integrasi Sarpras.

---

## Ringkasan Eksekutif

**Temuan inti arsitektur:** sistem punya TIGA keluarga data keuangan yang TIDAK saling terhubung:

1. **Ledger formal** (`jurnal_umums` + COA) — diisi otomatis hanya oleh KasMasuk/KasKeluar dan Sarpras (pengadaan/penyusutan), plus input manual.
2. **Tabel kas** (`kas_masuks`/`kas_keluars`) — nyambung ke ledger via observer poster. ✅
3. **Tabel operasional** (`pembayarans` SPP, `tabungan_siswas`, `slip_gajis`) — **tidak pernah menyentuh kas maupun jurnal**.

Akibatnya: **pendapatan terbesar sekolah (SPP), beban gaji, dan kewajiban tabungan siswa tidak pernah muncul di Neraca/Laba Rugi/Buku Besar**, sementara laporan operasional (LaporanPembayaran, LaporanTabungan, dashboard) menampilkannya — dua keluarga laporan tidak akan pernah rekonsil. Ini akar dari ±30% temuan.

| Severity | Jumlah | Karakter utama |
|---|---|---|
| Kritis | 2 | Buku bisa dibuat tidak balance lewat alur normal; dobel-akui pembayaran |
| Tinggi | 46 | Integrasi posting hilang, laporan salah rumus, RBAC laporan bolong |
| Sedang | 44 | Race condition, inkonsistensi antar laporan, kontrol lemah |
| Rendah | 7 | Performa, label, kolom mati |

**Penilaian flow vs sekolah pada umumnya:** fondasi double-entry sudah ada dan polanya bagus (COA lengkap, poster idempoten, laporan formal) — di atas rata-rata aplikasi sekolah. Tapi belum jadi siklus akuntansi sekolah utuh: **tidak ada tutup buku, tidak ada anggaran (RAPBS/RKAS), tidak ada BKU/pemisahan dana BOS, tidak ada laporan tunggakan**, dan ledger formal belum memuat transaksi operasional utama.

---

## 🔴 Temuan KRITIS (perbaiki duluan)

### K1 — Jurnal manual single-leg: buku bisa dibuat tidak balance dari UI
`app/Filament/Resources/JurnalUmums/Schemas/JurnalUmumForm.php:63-104` *(temuan #1, 3 verifikator konfirmasi)*

Form jurnal manual = satu baris (satu akun + debit XOR kredit; `afterStateUpdated` malah menolkan sisi lawan). Tidak ada validasi total debit = total kredit, tidak ada grouping antar baris (`nomor_bukti` malah unique per baris sehingga dua leg tak bisa satu bukti). Satu input manual → Neraca langsung "TIDAK SEIMBANG", Buku Besar bergeser.

**Fix:** ubah jadi header + detail (Repeater baris akun/debit/kredit), validasi `bccomp(total debit, total kredit) === 0` sebelum simpan, semua baris dalam satu `DB::transaction` dengan nomor bukti/group bersama.

### K2 — BuktiTransfer bisa menghasilkan DUA Pembayaran "berhasil" dari satu transfer
`app/Filament/Resources/BuktiTransfers/Pages/EditBuktiTransfer.php:42-49` *(temuan #2, #42)*

Cek idempotensi menyertakan `tagihan_siswa_id`: admin koreksi salah-pilih tagihan pada bukti yang sudah verified → simpan ulang → Pembayaran kedua dibuat untuk bukti yang sama (dobel-akui uang).

**Fix:** idempotensi cukup `referensi_pembayaran = 'BT-{id}'`; bila tagihan berubah, pindahkan `tagihan_siswa_id` Pembayaran lama (reconcile sudah mendukung), bukan buat baru. + test regresi.

---

## 🟠 Klaster Temuan TINGGI (dikonsolidasi)

### T1 — Pembayaran SPP, Gaji, dan Tabungan tidak pernah diposting ke kas/jurnal
*(temuan #8, #12, #13, #14, #19, #23, #25, #26, #36, #57 — akar masalah terbesar)*

- `Pembayaran` status berhasil → tidak ada KasMasuk/jurnal; akun `4-1001 Pendapatan SPP` & `1-2001 Piutang SPP` di COA tidak pernah dipakai; jembatan `UnitPos.akun_id` disiapkan tapi mati (`app/Models/Pembayaran.php:96-135`).
- `SlipGaji` status `paid` cuma dropdown — tidak membuat KasKeluar/jurnal; akun Beban Gaji & Hutang Gaji tidak pernah bergerak (`app/Models/SlipGaji.php:96-123`).
- `TabunganSiswa` = ledger terisolasi; COA tidak punya akun kewajiban "Titipan Tabungan Siswa" — kas titipan & kewajibannya sama-sama tak tampak di Neraca (`app/Models/TabunganSiswa.php:87-215`).

**Fix:** tiga poster baru meniru pola idempoten `KasJournalPoster` (jenis_referensi+referensi_id, reverse saat batal/hapus):
1. `PembayaranJournalPoster` — D Kas (resolve dari `UnitPos.akun_id`, fallback 1-1001) / K Pendapatan per jenis pembayaran.
2. `SlipGaji` workflow action Approve→Bayar — akrual D Beban Gaji / K Hutang Gaji, lalu bayar via KasKeluar otomatis.
3. `TabunganJournalPoster` + akun baru `2-1004 Titipan Tabungan Siswa` — setor: D Kas / K Titipan; tarik: kebalikan. Invariant: saldo akun = SUM saldo seluruh siswa.

### T2 — Neraca selalu "TIDAK SEIMBANG" karena laba berjalan tidak dihitung
*(#31, #32, #34, #35)* — `app/Filament/Pages/Neraca.php:88-119,206-210`

Neraca hanya menjumlah aset/liabilitas/ekuitas; pendapatan/beban dikecualikan dan tidak ada jurnal penutup → setiap posting KasMasuk membuat selisih = net income. `PerubahanModal` juga putus rantai antar periode (Modal Akhir Jan ≠ Modal Awal Feb).

**Fix:** baris ekuitas sintetis "Laba (Rugi) Berjalan" = `FinancialService::netIncome(awal pembukuan, tanggal)` di Neraca & PerubahanModal; jangka menengah: fitur jurnal penutup ke `3-2001 Laba Ditahan` (akun sudah ada, tak pernah dipakai).

### T3 — Saldo awal multi-tahun-ajaran terhitung DOBEL + boundary tanggal tidak konsisten
*(#33, #38, #39, #70, #87)* — `Neraca.php:185-191`, `BukuBesar.php:148-151`, `PerubahanModal.php:63-82`

- Semua laporan `SUM(saldo_awals)` lintas tahun ajaran + seluruh histori jurnal — begitu bendahara isi saldo awal TA ke-2 (carry-over saldo akhir TA-1), saldo dobel.
- Saldo awal bertanggal tepat hari pertama periode: Neraca pakai `<=`, BukuBesar/PerubahanModal pakai `<` → hilang dari dua laporan terakhir, angka antar laporan tak rekonsil.

**Fix:** semantik tegas — pakai hanya baris saldo awal TA yang relevan + jurnal sejak tanggal itu; seragamkan operator `<=` untuk saldo_awals.

### T4 — Form kas: label "Akun Kas/Bank" menyesatkan + posting bisa hilang diam-diam
*(#15, #16, #17, #62, #86)* — `KasMasukForm.php:22-27`, `KasJournalPoster.php:75-112`

Field `akun_id` sebenarnya akun LAWAN (pendapatan/beban) tapi dilabeli "Akun Kas/Bank" — user patuh label → pilih akun Kas → poster skip diam-diam (cuma `Log::warning`) → transaksi tampil di ArusKasBank tapi hilang dari Neraca/LabaRugi/BukuBesar. Plus: semua kas diposting ke satu akun `1-1001` — akun Bank BCA/Mandiri/BSI di COA tidak pernah bergerak.

**Fix:** ganti label "Akun Lawan (Pendapatan/Beban)" + scope opsi per tipe + validasi tolak akun kas; tambah kolom `kas_akun_id` (pilih Kas/Bank tujuan) dipakai poster; skip diam-diam → ValidationException.

### T5 — Flow BuktiTransfer bocor di beberapa transisi
*(#20, #22, #45, #68, #92)* — `EditBuktiTransfer.php:26-68`, `BuktiTransferForm.php:73-89`

- verified→rejected/pending atau ubah nominal pasca-verified TIDAK mereverse/menyinkronkan Pembayaran — transfer yang ditolak tetap diakui terbayar.
- Verifikasi membuat Pembayaran nominal penuh TANPA cek `sisa_tagihan` (jalur kasir menolak overpay, jalur transfer lolos).
- Verifikasi (mutasi finansial) cuma dropdown status biasa tanpa permission khusus & tanpa lock field pasca-verified.

**Fix:** afterSave simetris (un-verify → batalkan Pembayaran `BT-{id}`; nominal berubah → sinkron `jumlah_bayar`); validasi vs sisa tagihan; action "Verifikasi" ber-permission tersendiri + kunci field setelah verified.

### T6 — Jurnal otomatis bisa dirusak dari UI; reversal Sarpras tidak pernah terpanggil
*(#18, #21, #43, #44, #63, #66)*

- Baris jurnal hasil poster (kas/sarpras) bisa diedit/dihapus sebelah via resource JurnalUmum → pasangan D/K rusak (`JurnalUmumsTable.php:89-97`).
- `reverseJurnal()/reversePengadaan()` Sarpras **nol pemanggil** — hapus pengadaan diterima, jurnal tetap (`SarprasPengadaan.php:175-190`).
- Pengadaan mengkredit Kas langsung tanpa KasKeluar → kalau bendahara juga catat KasKeluar pembelian yang sama, kas terkredit 2× (`SarprasJournalPoster.php:40-100`).
- Semua item pengadaan dibuat tipe `bahan` tapi dijurnal sebagai Aset Tetap → bahan habis pakai jadi aset permanen yang tak disusutkan (`SarprasPengadaan.php:147-167`).

**Fix:** disable edit/delete baris jurnal ber-`jenis_referensi` otomatis (koreksi lewat dokumen sumber); observer deleted SarprasPengadaan → reverse; satukan jalur uang keluar pengadaan via KasKeluar; jurnal per tipe item (aset vs perlengkapan/beban).

### T7 — RBAC: seluruh halaman laporan keuangan TERBUKA untuk semua user login
*(#27, #28, #29, #30, #67)*

- **28 halaman custom Pages tidak punya gating apa pun** (tidak ada `HasPageShield`/`canAccess()`) — guru/petugas piket bisa buka Neraca, BukuBesar, **LaporanGaji (gaji seluruh pegawai!)** (`AuthPanelProvider.php`, `LaporanGaji.php:24-60`).
- Nominal SlipGaji `disabled()->dehydrated()` dihitung di klien dan dipercaya server — gaji_bersih bisa dimanipulasi dari payload Livewire (`SlipGajiForm.php:97-120`).
- Permission `View:SlipGaji` guru tidak discope ke slip miliknya — guru lihat gaji semua pegawai.
- Widget keuangan tanpa `HasWidgetShield`.

**Fix:** pasang `HasPageShield` di semua Pages laporan + assign permission per role di RoleSeeder; recompute nominal slip server-side dari SettingGaji di `mutateFormDataBeforeCreate/Save`; scoping `getEloquentQuery()` SlipGaji per pemilik untuk role guru; gate widget.

### T8 — Atomisitas & race condition
*(#41, #47, #83, #84, #85, #89, #90, #91)*

- `KasJournalPoster` menulis pasangan D/K dengan 2 insert TANPA `DB::transaction` (Sarpras poster sudah benar pakai transaksi); reverse→repost saat update juga non-atomik; idempotensi check-then-insert tanpa lock/unique index.
- Penarikan tabungan: validasi & insert tidak satu transaksi; tarik backdated/paralel lolos lalu `recalculate` melempar exception SETELAH row tersimpan (`TabunganSiswa.php:130-167`).
- Penomoran `KasMasuk/KasKeluar` MAX tanpa lock (race → unique violation 500); anti-race `SlipGaji` salah desain (lock dilepas sebelum insert).
- Validasi overpay kasir pakai float di luar transaksi — dua kasir paralel sama-sama lolos (`CreatePembayaran.php:29-36`).

**Fix:** bungkus poster + reverse/repost dalam transaksi; unique index `(jenis_referensi, referensi_id, sisi)`; validasi tarik tabungan dalam transaksi yang sama dengan insert; penomoran atomik/retry; pindahkan cek overpay ke dalam `reconcilePayment` (sudah pegang lock).

### T9 — Rumus/penyajian laporan salah
*(#37, #48, #69, #71-#82, #88, #97)*

- **LabaRugi tidak menampilkan total/laba-rugi sama sekali** — propertinya dihitung tapi tak pernah dirender (#77).
- ArusKasBank bukan laporan arus kas: tanpa saldo awal/akhir/total/klasifikasi aktivitas (#69, #74).
- LaporanPembayaran: "Sisa" = tagihan semester − pembayaran bulan berjalan → menyesatkan (#73, #78); LaporanKeuangan membandingkan dua basis tanggal berbeda (#72, #80); stats tagihan ikut menghitung status `batal` (#79).
- LaporanTabungan: saldo diambil dari row ID terbesar (bukan kronologis) → salah saat backdate; "Total Saldo" hanya siswa yang bertransaksi pada periode (#48, #81, #82).
- Akun ber-jurnal bisa di-soft-delete → total vs rincian LabaRugi beda, saldo lenyap dari Neraca (#37, #71, #76).
- LaporanPenyusutan pro-rata harian (float) vs jurnal bulanan — tak akan pernah cocok dengan akun akumulasi; opsi "Saldo Menurun" di form sebenarnya dihitung garis lurus (#88).
- LaporanPembayaranPerKelas pakai kelas SAAT INI untuk semester lampau (#97).

### T10 — Flow penagihan belum operasional untuk skala sekolah
*(#24, #46)*

- Tagihan SPP dibuat manual satu-per-satu per siswa; tidak ada kolom periode bulan; `PembayaranPaket` master tanpa action generate — ratusan siswa × 12 bulan tidak praktis (`ListTagihanSiswas.php`).
- Edit nominal/diskon tagihan yang sudah dibayar tidak merekalkulasi `sisa_tagihan`/status — tagihan "lunas" tetap lunas setelah nominal dinaikkan (`TagihanSiswaForm.php:104-114`).

---

## 🟡 Usulan Fitur/Menu Baru (terverifikasi belum ada, urut prioritas)

| # | Fitur | Alasan | Sketsa |
|---|---|---|---|
| F1 | **Neraca Saldo (trial balance)** | Alat deteksi buku tak balance — mendesak selama K1 ada | Page agregat jurnal GROUP BY akun + saldo awal, footer total D vs K (#3) |
| F2 | **Tutup buku / kunci periode** | Laporan yang sudah diserahkan yayasan bisa berubah retroaktif | Tabel `periode_akuntansis` (open/closed) + guard tanggal di create/update/delete jurnal, kas, pembayaran (#6, #7, #64) |
| F3 | **Generate Saldo Awal otomatis dari TA lalu** | Saldo awal manual rawan salah ketik & dobel (T3) | Action: hitung saldo akhir per akun + tutup pendapatan/beban ke Laba Ditahan → insert massal idempoten (#55) |
| F4 | **BKU + dimensi sumber dana (BOS/komite/yayasan)** | Sekolah pengelola BOS wajib BKU format juknis; sekarang sumber dana cuma ada di Sarpras | Kolom `sumber_dana` di kas + halaman BKU (tanggal, no bukti, uraian, penerimaan, pengeluaran, saldo berjalan) + cetak PDF (#4, #10) |
| F5 | **Anggaran RAPBS/RKAS vs Realisasi** | Dokumen perencanaan wajib sekolah; realisasi sudah ada di jurnal per akun | Tabel `anggarans` (TA, akun, pagu) + laporan Anggaran/Realisasi/Selisih/% (#9, #11) |
| F6 | **Ekspor PDF semua laporan keuangan** | Laporan ke yayasan/dinas sekarang hanya bisa dilihat di layar; dompdf sudah terpasang (pola BukuPribadiService tinggal ditiru) | Header action "Cetak PDF" per laporan: kop sekolah + tabel + TTD bendahara/kepsek (#5, #52) |
| F7 | **Laporan Tunggakan + umur piutang (aging)** | Laporan rutin wajib bendahara; data sisa_tagihan + jatuh tempo sudah ada | Bucket 1-30/31-60/61-90/>90 hari per siswa/kelas + cetak Surat Tagihan per siswa (#51, #58) |
| F8 | **Generate Tagihan Massal per periode** | Prasyarat operasional SPP bulanan (T10) | Kolom periode + action massal idempoten per siswa+jenis+periode; "Terapkan Paket" (#24) |
| F9 | **Rekonsiliasi bank** | 3 akun bank di COA + flow transfer membutuhkannya | Input/import mutasi rekening koran, matching ke jurnal akun bank, selisih outstanding (#49, #50) |
| F10 | **Nomor bukti jurnal sekuensial** | Nomor acak yang bisa diedit = celah audit (gap detection mustahil) | `JU-YYYYMM-NNNN` di `booted()` creating, atomik (#56) |
| F11 | **Role `kepala_sekolah` + Dashboard Keuangan read-only** | Role-nya bahkan belum ada | Tren 12 bulan, tunggakan per kelas, saldo kas/bank, realisasi vs RAPBS (#54) |
| F12 | **KirimTagihan via WA — implementasi nyata** | Halaman sudah ada tapi cuma notifikasi "Coming Soon" | Gateway WA (Fonnte/Wablas) + queued job + log terkirim (#59) |
| F13 | Filter/komparasi tahun ajaran di laporan akuntansi | Saldo awal sudah berdimensi TA, laporan belum (#53) | |
| F14 | Kas kecil (imprest) | Nice-to-have (#93) | |

---

## Temuan Susulan Critic (4) — *terdeteksi, verifikasi terputus limit; perlakukan sebagai temuan kuat belum-divoting*

1. **Sarpras non-pengadaan tidak menyentuh pembukuan**: penghapusan aset (write-off), biaya pemeliharaan, dan denda peminjaman tidak pernah dijurnal.
2. **Modul Pajak yatim total**: master pajak lengkap (resource, policy, seeder) tapi tidak dipakai perhitungan apa pun — termasuk tidak dipakai potongan PPh slip gaji.
3. **Seeder keuangan memalsukan keadaan**: men-seed jurnal SPP/gaji yang tidak pernah dibuat kode produksi + `WithoutModelEvents` memutus observer posting — demo data terlihat benar, produksi tidak akan pernah menyamai.
4. **Scheduler keuangan rapuh**: `sarpras:susut-bulanan` tanpa catch-up periode terlewat (server mati di tanggal eksekusi = sebulan penyusutan hilang), dan tidak ada job terjadwal keuangan lain.

---

## ✅ Keputusan Bisnis (FINAL — hasil diskusi 2026-06-13)

| # | Keputusan | Pilihan | Konsekuensi implementasi |
|---|---|---|---|
| 1 | Basis pengakuan pendapatan SPP | **KAS** — pendapatan diakui saat dibayar | `PembayaranJournalPoster` satu jurnal saja: D Kas / K Pendapatan saat status `berhasil`. TIDAK pakai akun Piutang SPP di ledger; tunggakan tetap dari `TagihanSiswa.sisa_tagihan` (laporan F7) |
| 2 | Perlakuan tabungan siswa | **Titipan/kewajiban** (akun baru `2-1004 Titipan Tabungan Siswa`) | Setor: D Kas / K Titipan; tarik: kebalikan. Invariant: saldo akun 2-1004 = SUM saldo seluruh siswa |
| 3 | Jurnal historis | **CUT-OFF 1 Juli 2026** (awal TA 2026/2027), TANPA backfill | Poster otomatis hanya memposting transaksi bertanggal ≥ 2026-07-01 (konstanta/config `cutoff`). Saldo awal TA 2026/2027 diisi sekali (dibantu fitur F3). Transaksi lama tidak dijurnal ulang |
| 4 | Anti-dobel pasca cut-off | KasMasuk manual kategori SPP **diblok/diperingatkan** setelah Wave 2 aktif | Validasi di KasMasukForm: kategori/keterangan SPP → arahkan ke modul Pembayaran (posting otomatis), supaya tidak dobel dengan poster |
| 5 | Prioritas Wave 4 | **Semua 4 fitur**: BKU+sumber dana, tagihan massal, tutup buku+roll-forward, RAPBS | Rekonsiliasi bank (F9) & dashboard kepsek (F11) digeser ke Wave 5 |
| 6 | Urutan eksekusi | **0 → 1 → 2 → 3 → 4 → 5, wajib urut** | Tiap wave prasyarat berikutnya; tiap wave: implementasi → test Pest → pint → full suite |

## Rencana Perbaikan Bertahap (wave)

| Wave | Isi | Temuan tertutup |
|---|---|---|
| **0 — Darurat** ✅ **SELESAI 2026-06-13** (full suite 352 pass) | K1 (form jurnal create jadi multi-baris balanced + nomor bukti sekuensial atomik + drop unique nomor_bukti), K2 (idempotensi BT by referensi + pindah tagihan via update), T7 (HasPageShield 26 halaman + mapping RoleSeeder, SlipGaji recompute server-side + scoping kepemilikan, FinancialOverview canView) | 2 kritis + 5 tinggi keamanan |
| **1 — Integritas ledger** ✅ **SELESAI 2026-06-13** (full suite 389 pass) | T4 (kolom `kas_akun_id` Kas/Bank per transaksi + label 'Akun Lawan' + validasi tolak akun sama), T6 (guard policy+UI jurnal auto-posted, blokir delete pengadaan diterima, klasifikasi bahan→Perlengkapan), T8 (poster kas atomik tx+lock, penomoran kas/slip atomik, tarik tabungan satu transaksi + cek timeline penuh, overpay divalidasi dalam lock — jalur BT bypass eksplisit menunggu #45), T3 (semantik snapshot saldo awal terpusat di FinancialService: `saldoAwalSnapshotPerAkun`/`saldoPerAkun`/`saldoAwalPeriodePerAkun` + boundary konsisten), T2 (baris sintetis 'Laba (Rugi) Berjalan' di Neraca → SEIMBANG; rantai Modal Akhir==Modal Awal antar periode), F1 (halaman NeracaSaldo baru + shield + RoleSeeder), F10 (nomor JU- sekuensial — dikerjakan di Wave 0) | ±20 temuan |
| **2 — Integrasi posting** ✅ **SELESAI 2026-06-13** (full suite 428 pass) *(basis KAS, cut-off 2026-07-01)* | T1: `config/akuntansi.php` (cut-off + map akun) + 4 akun baru COA (2-1004 Titipan Tabungan, 5-3003 Beban Pemeliharaan, 5-5002 Kerugian Penghapusan, 4-1006 Pendapatan Denda); PembayaranJournalPoster (D Kas via UnitPos.akun_id / K Pendapatan per JenisPembayaran.akun_pendapatan_id) + blok KasMasuk manual SPP; SlipGaji workflow Approve(akrual D Beban Gaji/K Hutang Gaji)→Bayar(KasKeluar auto-jurnal D Hutang/K Kas); TabunganJournalPoster (setor D Kas/K Titipan, tarik kebalikan, invariant saldo 2-1004=Σsaldo siswa); susulan #1 sarpras non-pengadaan (penghapusan write-off nilai buku, pemeliharaan, denda). Semua idempoten + reverse + cut-off gate. | ±12 temuan — *prasyarat laporan akurat* |
| **3 — Pembenahan laporan** ✅ **SELESAI 2026-06-13** (full suite 483 pass) | T9 lengkap: LabaRugi tampilkan TOTAL pendapatan/beban/laba-rugi; ArusKas jadi laporan arus kas riil (saldo awal/akhir + klasifikasi operasi/investasi/pendanaan); LaporanPembayaran 'Sisa' = sisa riil; LaporanKeuangan dua-basis-tanggal berlabel + kecualikan batal; LaporanTagihanSiswa agregat kecualikan batal; LaporanTabungan saldo kronologis + total semua siswa; akun ber-jurnal tak bisa dihapus + perlakuan `withTrashed` seragam (total==rincian); PenyusutanService bulan-integer + saldo_menurun nyata; LaporanPembayaranPerKelas kelas historis via KenaikanKelas. **F6**: infrastruktur ekspor PDF bersama (`LaporanPdfService` + blade generik) + tombol 'Cetak PDF' di 17 halaman laporan keuangan. **F7**: halaman baru Laporan Tunggakan + umur piutang (aging bucket) + PDF. | ±15 temuan |
| **4 — Siklus akuntansi sekolah** ✅ **SELESAI 2026-06-13** (full suite 540 pass) | F4: kolom `sumber_dana` (BOS/komite/yayasan) di kas + halaman **Buku Kas Umum** (saldo berjalan, filter sumber dana, cetak PDF). F8: kolom periode di tagihan + **Generate Tagihan Massal** per jenis/kelas/bulan (idempoten) + Terapkan Paket. F2: tabel `periode_akuntansis` + **Tutup Buku** — guard terpusat di AppServiceProvider memblok create/edit/delete transaksi pada bulan tertutup (JurnalUmum/Kas/Pembayaran/Tabungan), reopen super_admin-only. F3: **Roll-forward Saldo Awal** — generate saldo awal TA baru dari saldo akhir TA lama + tutup laba/rugi ke Laba Ditahan (Neraca TA baru seimbang), idempoten. F5: tabel `anggarans` + resource + halaman **Laporan RAPBS vs Realisasi** (Anggaran/Realisasi/Selisih/% serapan dari jurnal) + PDF. | fitur prioritas terpilih |
| **5 — Pelengkap** ✅ **SELESAI 2026-06-13** (full suite 613 pass) | Pajak (#2): potongan PPh % di slip gaji + jurnal split D Beban / K Hutang Pajak 2-1003 / K Hutang Gaji (aditif, tanpa-pajak tak berubah). F9: rekonsiliasi bank (mutasi rekening koran vs saldo buku + outstanding). F14: halaman Kas Kecil (akun 1-1005). F11: 3 widget keuangan (tren 12 bulan, saldo kas/bank, ringkasan tunggakan) tampil di **dashboard utama** (bukan halaman terpisah), di-gate `canView()` per role — terlihat oleh bendahara/super_admin/kepala_sekolah, tersembunyi dari guru dll; + role baru `kepala_sekolah` read-only. F12: notifikasi WA tagihan (tabel log + queued job + driver swappable, default log — tanpa dependensi berbayar) + halaman KirimTagihan nyata + bulk kirim. Seeder jujur (#3): hapus jurnal SPP/gaji palsu, demo pasca-cut-off lewat poster nyata. Scheduler (#4): SusutBulanan idempoten + catch-up periode terlewat. Performa: #98 LaporanDebitKredit agregasi SQL, #99 PembayaranForm anti-N+1. (F13 filter tahun ajaran ditunda — opsional.) | sisa |

Tiap wave: implementasi → test Pest (skenario per temuan, terutama regresi K1/K2/T5/T8) → `vendor/bin/pint --dirty` → full suite. Wave 2 menjadi prasyarat sebelum laporan formal dipakai sebagai sumber kebenaran tunggal.

**Catatan migrasi:** server produksi berisi data nyata — semua kolom baru nullable/backfill idempoten; perubahan semantik saldo awal & posting butuh skrip backfill jurnal historis (opsional, dijalankan manual + verifikasi Neraca Saldo).

---

## Lampiran — Indeks 99 Temuan Terkonfirmasi

> Format: `#id [severity/type/area] file — ringkasan`. Detail lengkap (evidence + alasan verifikator) tersimpan di hasil workflow audit.

**KRITIS**
1. [kritis/logic/ledger] JurnalUmumForm.php:63-104 — jurnal manual single-leg tanpa validasi balance.
2. [kritis/logic/pembayaran] EditBuktiTransfer.php:42-49 — ganti tagihan pasca-verified → Pembayaran dobel.

**TINGGI**
3. [fitur/laporan] — Neraca Saldo belum ada. 4. [fitur/laporan] — BKU & penanda sumber dana BOS tidak ada. 5. [fitur/laporan] — nol ekspor PDF/Excel di semua laporan. 6-7. [fitur/ledger] — tidak ada tutup buku/kunci periode. 8. [fitur/lintas] FinancialOverview — SPP/gaji/tabungan tak pernah dijurnal. 9, 11. [fitur/lintas] — modul RAPBS tidak ada. 10. [fitur/lintas] — pemisahan dana BOS tidak ada. 12. [fitur/lintas] Pembayaran.php — UnitPos.akun_id mati, SPP tak masuk LabaRugi. 13-14. [flow/gaji] SlipGaji — status paid tanpa efek finansial. 15-17. [flow/kas] KasMasukForm — label "Akun Kas/Bank" menyesatkan, posting skip diam-diam. 18. [flow/ledger] JurnalUmumsTable — jurnal otomatis bisa diedit/dihapus sebelah. 19-20, 22. [flow] EditBuktiTransfer — un-verify tidak reverse Pembayaran. 21. [flow/lintas] SarprasPengadaan — reverseJurnal nol pemanggil. 23, 25. [flow/pembayaran] — SPP tidak masuk kas/jurnal (akun COA siap, tak terpakai). 24. [flow/pembayaran] — tidak ada generate tagihan massal/periode. 26. [flow/tabungan] — tabungan tak tercermin di pembukuan. 27. [keamanan/gaji] LaporanGaji — gaji semua pegawai terbuka tanpa permission. 28. [keamanan/gaji] SlipGajiForm — nominal dipercaya dari klien. 29. [keamanan/gaji] SlipGajiPolicy — view guru tak discope miliknya. 30. [keamanan/laporan] — 28 halaman laporan tanpa gate. 31-32, 34. [laporan] Neraca — laba berjalan tak dihitung → selalu "TIDAK SEIMBANG". 33. [laporan] BukuBesar:148-151 — saldo awal tanggal = awal periode hilang (`<` vs `<=`). 35. [laporan] PerubahanModal — modal akhir ≠ modal awal periode berikut. 36. [laporan/tabungan] — kewajiban titipan tak ada di COA/Neraca. 37. [logic/laporan] FinancialService:56 — akun soft-delete: total ≠ rincian. 38-39. [logic] Neraca:185-191 — saldo awal SUM lintas TA → dobel. 40. [logic/ledger] — duplikat K1. 41. [logic/ledger] KasJournalPoster — 2 insert tanpa transaksi. 42. [logic/lintas] — duplikat K2. 43. [logic/lintas] SarprasJournalPoster — kredit kas tanpa KasKeluar → potensi kas terkredit 2×. 44. [logic/lintas] SarprasPengadaan:147-167 — item bahan dijurnal sebagai Aset Tetap, tak pernah disusutkan. 45. [logic/pembayaran] EditBuktiTransfer — verifikasi tanpa cek sisa tagihan (overpay lolos). 46. [logic/pembayaran] TagihanSiswaForm — edit nominal tagihan terbayar tak rekalkulasi status. 47. [logic/tabungan] TabunganSiswa — penarikan tak atomik; backdate/paralel merusak saldo. 48. [logic/tabungan] LaporanTabungan:75 — saldo dari row ID terbesar, bukan kronologis.

**SEDANG**
49-50. [fitur/kas] — rekonsiliasi bank tidak ada; satu akun kas 1-1001 untuk semua. 51, 58. [fitur] — laporan tunggakan/aging + surat tagihan tidak ada. 52. [fitur] — ekspor PDF laporan (duplikat #5, prioritas diturunkan verifikator). 53. [fitur] — filter/komparasi tahun ajaran di laporan. 54. [fitur] — role kepala_sekolah + dashboard keuangan tidak ada. 55. [fitur/ledger] — roll-forward saldo awal antar TA manual. 56. [fitur/ledger] — nomor bukti jurnal acak & bisa diedit. 57. [fitur/lintas] — duplikat T1. 59. [fitur/pembayaran] KirimTagihan — masih "Coming Soon". 60, 65. [fitur+flow/pembayaran] — PosBayar yatim; UnitPos.akun_id tak dipakai; Pembayaran via BT tanpa unit_pos_id. 61. [flow/gaji] — status SlipGaji dropdown bebas tanpa guard transisi/jejak approval. 62. [flow/kas] — duplikat T4. 63. [flow/ledger] — duplikat #21. 64. [flow/ledger] — duplikat F2 + tanpa carry-forward. 66. [keamanan/ledger] EditJurnalUmum — delete tanpa guard record final. 67. [keamanan/lintas] FinancialOverview — widget tanpa gate. 68. [keamanan/pembayaran] BuktiTransferForm — verifikasi cuma dropdown tanpa permission khusus. 69, 74. [laporan/kas] ArusKasBank — bukan laporan arus kas (tanpa saldo/total/klasifikasi). 70. [laporan] — boundary saldo awal `<` vs `<=` antar laporan. 71, 76. [laporan] LabaRugi join tanpa filter akuns.deleted_at — rincian ≠ total. 72, 80. [laporan] LaporanKeuangan — dua basis tanggal dibandingkan langsung. 73, 78. [laporan] LaporanPembayaran — "Sisa" salah rumus saat filter tanggal aktif. 75, 96. [laporan/lintas] FinancialOverview — tiga keluarga angka campur tanpa rekonsiliasi. 77. [laporan] LabaRugi — total laba/rugi dihitung tapi TIDAK pernah ditampilkan. 79. [laporan/pembayaran] stats tagihan ikut hitung status batal. 81-82. [laporan/tabungan] — saldo & total saldo LaporanTabungan salah. 83. [logic/gaji] SlipGaji — anti-race penomoran salah desain (lock lepas sebelum insert). 84, 90. [logic/kas+ledger] KasJournalPoster tanpa transaksi (vs Sarpras yang benar). 85. [logic/kas] penomoran KasMasuk/KasKeluar tanpa lock + wrap 9999. 86. [logic/kas] — duplikat T4 (skip diam-diam). 87. [logic/laporan] — duplikat T3. 88. [logic/laporan] PenyusutanService — pro-rata float vs posting bulanan; "Saldo Menurun" palsu. 89. [logic/ledger] idempotensi check-then-insert tanpa lock/unique. 91. [logic/pembayaran] CreatePembayaran — cek overpay float di luar lock (race 2 kasir). 92. [logic/pembayaran] — duplikat #45.

**RENDAH**
93. [fitur/kas] — kas kecil (imprest) tidak ada. 94. [flow/ledger] SaldoAwalForm — soft-deleted menempati unique index, input ulang buntu. 95. [keamanan/ledger] Akun.php — kolom saldo_awal/saldo_akhir fillable tapi mati/rancu. 96. — lihat #75. 97. [laporan/pembayaran] LaporanPembayaranPerKelas — kelas saat ini dipakai untuk semester lampau. 98. [performa] ArusKasBank/LaporanDebitKredit — agregasi in-memory, paginasi 'all'. 99. [performa] PembayaranForm — N+1 label tagihan.

---

## Langkah Berikut

Keputusan bisnis sudah final (lihat tabel ✅ di atas). Eksekusi tinggal perintah per wave, urut dari **Wave 0**.

Sisa konfirmasi kecil yang bisa diputuskan saat wave terkait berjalan:
- Daftar nilai `sumber_dana` (default usulan: bos / komite / yayasan / lainnya) — dipakai Wave 4 (F4).
- Mapping akun pendapatan per JenisPembayaran (default: satu akun `4-1001 Pendapatan SPP`; bisa dipecah per jenis) — dipakai Wave 2.
- Format BKU mengikuti juknis BOS yang dipakai sekolah — dipakai Wave 4 (F4).
