# Bug Audit — si-sekolah (SIAKAD)

**Date:** 2026-06-06
**Method:** 6 parallel code-review agents (RFID, payments, accounting, models, Filament resources, auth/security), opus/sonnet, verified against source.
**Scope:** logic + code bugs only (not style). Read-only audit — nothing modified.
**Verdict:** REQUEST CHANGES.

**Counts (deduplicated):** 🔴 7 · 🟠 18 · 🟡 ~20 · 🔵 ~12

> Severity: 🔴 critical (data corruption / money / security) · 🟠 high · 🟡 medium · 🔵 low.
> Each item needs a Pest test with its fix (per CLAUDE.md).

---

## 🔴 CRITICAL (7)

| # | File:line | Problem | Fix |
|---|-----------|---------|-----|
| C1 | `app/Models/Semester.php:86` | `activate()` bulk-deactivate has no `tahun_ajaran_id` scope → activating one semester deactivates the active semester of **every** academic year. Reachable from `SemestersTable.php:47`, `CreateSemester.php:20`, `EditSemester.php:30`. | Scope the update: `self::where('tahun_ajaran_id', $this->tahun_ajaran_id)->where('id','!=',$this->id)->update(['is_active'=>false])`. |
| C2 | `app/Services/Rfid/PresensiScanService.php:53` | Debounce/duplicate check runs **outside** `DB::transaction`+`lockForUpdate` (lock starts line 64). Concurrent taps both pass `isDuplicateScan` → double masuk. DB unique on `(siswa_id,tanggal)` turns it into a hard error, not silent corruption. | Move debounce inside the transaction, or rely on a DB unique constraint to serialize. |
| C3 | `app/Models/TabunganSiswa.php:76-92` | Denormalized `saldo` frozen at `creating` from a SUM with no lock/transaction and no `updated`/`deleted`/`restored` handlers → overdraft race + every edit/delete/restore desyncs the running balance permanently. | Wrap create in txn + `lockForUpdate`; treat `saldo` as derived (recompute everywhere or drop column); add update/delete/restore handlers; re-validate sufficiency on edit. |
| C4 | `app/Models/Pembayaran.php:130,139,174-175` | `decimal:2` casts return **strings**, cast to `float` and summed/subtracted → binary-float drift across apply/reverse cycles; `total_terbayar + sisa_tagihan` diverges from `total_tagihan`. Float equality `=== 0.0` at `:147` also fragile. | Use integer minor units (cents) or `bcadd`/`bcsub` scale 2; compare with tolerance/BC. |
| C5 | `app/Models/TagihanSiswa.php:98` | `updateStatus()` marks `lunas` whenever `sisa_tagihan <= 0` → overpayment silently reads as fully paid; negative `sisa` accepted. No model-layer overpay guard (only Filament pages, bypassed by factories/seeders/bulk writes). | Clamp `sisa_tagihan = max(0, total_tagihan - total_terbayar)`; reject/flag overpay in the locked apply path. |
| C6 | `app/Models/KasMasuk.php:73-83`, `app/Models/KasKeluar.php:73-83`, `app/Filament/Pages/Neraca.php:50-187` | Kas Masuk/Keluar are **never posted to `jurnal_umums`** (no observer). `Neraca`/`LabaRugi`/`BukuBesar` read only the ledger → cash transactions invisible to the statements and **Neraca can't balance**; if also entered as a manual jurnal → double-counted. Neraca also computes no totals and never asserts Aset = Liabilitas + Ekuitas. | Post balanced double-entry jurnal rows via `KasMasuk`/`KasKeluar` model observers; add a Neraca balance assertion row. Plan a backfill for historical kas data. |
| C7 | `LabaRugi.php:108` vs `PerubahanModal.php:82` vs `FinancialOverview.php:39` | Net income / "saldo" computed 3 incompatible ways: ledger `pendapatan−beban`, independent recompute, and cash `KasMasuk−KasKeluar`. They can never agree (cash tables disjoint from ledger). | Define one canonical net-income/cash service and reuse everywhere. |

---

## 🟠 HIGH (18)

### RFID / API
| # | File:line | Problem | Fix |
|---|-----------|---------|-----|
| H1 | `app/Http/Controllers/Api/RfidScanController.php:114` | `Carbon::parse(scanned_at)` keeps the client offset instead of converting to `Asia/Jakarta`; a device sending UTC/other offset makes `jam_masuk`, terlambat math, and pulang gate wrong. | `->setTimezone(config('app.timezone'))` after parse. |
| H2 | `app/Http/Requests/RfidScanRequest.php:22` | `scanned_at` rule is just `nullable|date` — accepts arbitrary past/future → attendance backdating + debounce bypass (future timestamp has no prior log). | Add `before:+2 minutes`, `after:-1 day`. |
| H3 | `bootstrap/app.php:16-19`, `routes/api.php:6-8` | No rate limiting on the public scan endpoint (`throttleApi` never called, `api` group has no limiter) → brute-force UID enumeration / DoS by DB-write flooding. | `$middleware->throttleApi(limiter:'60,1')` (or per-device/IP). |
| H4 | `app/Http/Middleware/AuthenticateRfidDevice.php:26-29` | Loads **all** active devices and runs `Hash::check` (bcrypt) per device → O(n·bcrypt) per request, CPU-exhaustion DoS, no index possible. | Store an indexed `token_prefix` (first chars) to pre-filter to one candidate before `Hash::check`. |
| H5 | `app/Services/Rfid/PresensiScanService.php:187` | Debounce uses strict `where('scanned_at','<',$scannedAt)`; two requests with identical `scanned_at` don't see each other → both bypass debounce (compounds C2). | Use `<=` with id tie-break, or debounce against server `now()` not client time. |
| H6 | `app/Models/KartuRfid.php:151-167` + `app/Filament/Resources/KartuRfids/Schemas/KartuRfidForm.php:26` | `updating` hook only dedups when `status` becomes `aktif`; changing `owner_id`/`owner_type` on an already-aktif card skips dedup → two active cards per owner. Form `owner_id` not scoped to owners without an active card; no DB unique on `(owner_type,owner_id,status=aktif)`. | Guard dedup on owner change too; filter form options; consider partial unique index. |

### Money / billing
| # | File:line | Problem | Fix |
|---|-----------|---------|-----|
| H7 | `app/Filament/Resources/BuktiTransfers/*` | Verifying a BuktiTransfer (`status→verified`) has **no financial effect** — never creates a Pembayaran nor reduces the linked tagihan; `verified_by`/`verified_at` not even auto-set. Admins "approve" payments that never post. | On transition to `verified`, create the matching `Pembayaran` (berhasil) in a txn, set verifier fields, guard double-verify. |
| H8 | `app/Models/Pembayaran.php:113` | No `forceDeleted` handler → force-deleting a `berhasil` payment (or tagihan `cascadeOnDelete`) destroys it without reversing the amount → `total_terbayar` overstated. | Add `forceDeleted` reversal; avoid double-reverse when already soft-deleted. |
| H9 | `app/Models/Pembayaran.php:113-123` | `deleted`/`restored` key off current mutable `status`. Soft-delete while `gagal` (no reverse) → status flipped to `berhasil` → restore applies +amount never reversed → inflated tagihan. | Track an explicit `applied_amount`/`applied_at` ledger field; reconcile against it, not against `status`. |
| H10 | `app/Filament/Resources/Pembayarans/Pages/EditPembayaran.php:40-46` | Sisa-tagihan re-add only fires when old `status==='berhasil'`; editing a `pending`/`gagal→berhasil` payment mis-validates overpayment. Validation duplicates and can diverge from `reconcileUpdatedPayment`. | Derive validation delta from the same old/new applied-amount logic the hook uses; or recompute sisa live under lock. |

### Accounting reports
| # | File:line | Problem | Fix |
|---|-----------|---------|-----|
| H11 | `app/Filament/Pages/BukuBesar.php:56-126` | Not a real general ledger: no running-balance column, no per-akun opening balance from `saldo_awals`, akun filter optional (accounts intermixed). | Require akun filter, seed opening from `saldo_awals`, compute running saldo honoring `posisi_normal`. |
| H12 | `app/Filament/Pages/Neraca.php:170-180` | Sign derived from akun `tipe`, ignoring the stored `posisi_normal` column → contra accounts (e.g. akumulasi penyusutan) shown with wrong sign. | Drive sign from `akun.posisi_normal`. |
| H13 | `app/Filament/Pages/PerubahanModal.php:83-84` | Prive hardcoded `0` → owner withdrawals dropped, ending modal overstated. | Source prive from the prive/equity akun(s) over the period. |
| H14 | `app/Filament/Pages/PerubahanModal.php:61-65` | `modalAwal` excludes `saldo_awals` (unlike Neraca) → opening modal disagrees with Neraca opening equity. | Include `saldo_awals` for ekuitas accounts, matching `Neraca::calculateSaldoPerAkun()`. |
| H15 | `app/Filament/Pages/LaporanPembayaran.php:81-102` | Date filter applied only to the `pembayarans` sub-collection; `total_tagihan`/`total_sisa`/counts computed on the unfiltered set → columns describe different populations, won't reconcile. | Apply the date filter consistently to all aggregates. |
| H16 | `app/Filament/Pages/LabaRugi.php:74-104` (also ArusKasBank, LaporanDebitKredit, LaporanPembayaranPerTanggal) | Loads all matching rows into PHP then `->groupBy()` in memory → memory exhaustion at scale (correctness OK). | Aggregate in SQL: `selectRaw('akun_id, SUM(kredit)-SUM(debit)')->groupBy('akun_id')`. |

### HR / forms
| # | File:line | Problem | Fix |
|---|-----------|---------|-----|
| H17 | `app/Models/SlipGaji.php:100-109` | Auto-numbering reads `max('nomor')` then writes without lock → concurrent creates collide on the `unique` nomor (ungraceful QueryException). LIKE filter (`Y%`) vs prefix granularity (`Ym-`) mismatch → counter doesn't reset monthly as the prefix implies. | Wrap in txn + `lockForUpdate`, or catch unique violation + retry; align LIKE filter to prefix. |
| H18 | KelasForm:28, SemesterForm:36, JenisPembayaranForm:43, KenaikanKelasForm:24, KelulusanForm:24, JadwalPelajaranForm, SaldoAwalForm, CreateSlipGaji | DB composite-unique indexes not validated in forms → duplicate submit throws raw `SQLSTATE[23000]` instead of a field error. JadwalPelajaran also misses teacher double-booking check. SaldoAwal has no `(akun_id,tahun_ajaran_id)` unique at all. | Add `->unique(..., ignoreRecord:true, modifyRuleUsing: scope)` per composite key, or guard in `mutateFormDataBeforeCreate`. Add the missing SaldoAwal DB unique. |

---

## 🟡 MEDIUM (~20)

| File:line | Problem | Fix |
|-----------|---------|-----|
| `app/Models/Kelas.php:104-107` | `getJumlahSiswaAttribute()` runs `count()` per instance → N+1 in lists; also counts inactive students. | `withCount('siswas')`; scope to active if intended. |
| `app/Filament/Resources/SettingGajis/Schemas/SettingGajiForm.php:34,44-72` | All salary/tunjangan/potongan fields allow negatives → corrupt `gaji_bersih`. | `->minValue(0)` (gaji_pokok `->minValue(1)`). |
| `app/Filament/Resources/TagihanSiswas/Schemas/TagihanSiswaForm.php:115` | `diskon` allows negatives → `total_tagihan` exceeds `nominal`. | `->minValue(0)`, optional `->maxValue(nominal)`. |
| `app/Filament/Resources/SlipGajis/Schemas/SlipGajiForm.php:127-128` | `detail_tunjangan`/`detail_potongan` are array casts held in `TextInput` → data lost on edit roundtrip. | Use `Hidden::make()` or `KeyValue`. |
| `app/Filament/Resources/Tahfidzs/Schemas/TahfidzForm.php:80` | No `ayat_selesai >= ayat_mulai` validation; `calculateJumlahAyat` silently no-ops on bad order. | `->gte('ayat_mulai')` on `ayat_selesai`. |
| `app/Filament/Resources/JurnalUmums/Schemas/JurnalUmumForm.php:72,81` | Both `debit` and `kredit` can be 0 → zero-zero journal entry saved. | Rule rejecting `debit==0 && kredit==0`. |
| `app/Filament/Resources/Absensis/Tables/AbsensisTable.php:21-22` | 2-level nested relation columns without eager load → N+1. | `getEloquentQuery()->with(['jadwalPelajaran.kelas','jadwalPelajaran.mataPelajaran','siswa'])`. |
| `app/Filament/Widgets/FinancialOverview.php:99-113` | `getMonthlyTrend` uses `DB::table()` without `whereNull('deleted_at')` → soft-deleted kas leak; chart disagrees with headline stat. Window also < 5 bars Jan–Apr, no year-cross. | Add soft-delete filter; rolling 5-month window. |
| `app/Filament/Pages/LaporanSiswa.php:73-80` | Raw join with double-quoted SQL literals (`"L"`,`"P"`) → return 0 under `ANSI_QUOTES`; join bypasses soft-delete scope. | Single-quoted literals + `whereNull('deleted_at')`. |
| `app/Models/JurnalUmum.php:24-26` | `referensi`/`jenis_referensi`/`referensi_id` pseudo-polymorphic, no `morphTo`/morphMap/enum → no integrity, scattered raw `where`. | Add `morphTo` + `Relation::morphMap`, or constrain `jenis_referensi` to enum. |
| `app/Filament/Resources/JurnalUmums/Schemas/JurnalUmumForm.php:110-115` | `created_by` `disabled()->dehydrated()` → submitted from browser, tamperable on **update** (create path forces it, update doesn't). | Drop `dehydrated()`; force server-side on create only. |
| `.env.example:4` | `APP_DEBUG=true` default → new deploys leak stack traces/env. | `APP_DEBUG=false`. |
| `config/session.php:172` / `.env.example` | `SESSION_SECURE_COOKIE` absent → session cookies over plain HTTP. | Add `SESSION_SECURE_COOKIE=true` (note: false for local HTTP). |
| `app/Services/Rfid/PresensiScanService.php:51` | `Sekolah::first()` nondeterministic if multiple rows; missing row disables debounce entirely. | Singleton lookup; apply debounce defaults even when row missing. |
| `app/Filament/Resources/.../CreatePresensiHarian.php:20` & `CreatePresensiHarianPegawai.php:20` | `whereDate('tanggal', $data['tanggal'] ?? null)` → with null, matches nothing, duplicate check silently passes (field is required, so defence-in-depth gap). | Explicit null guard before query. |
| `app/Filament/Resources/KartuRfids/Schemas/KartuRfidForm.php:26` | (see H6) owner not scoped to those without active card. | Filter options / pre-save guard. |
| `app/Filament/Pages/LaporanPembayaran.php:86,90` | Carbon-vs-string date comparison fragile. | Parse bounds with `Carbon::parse(...)->startOfDay()/endOfDay()`. |
| `app/Filament/Pages/LaporanPembayaran.php:95-96` vs `LaporanPembayaranPerKelas.php:84` | Two "terbayar" sources (live sum vs denormalized column) diverge. | One source of truth. |
| `app/Filament/Pages/LaporanTahfidz.php:95` | School avg = unweighted mean of per-student means → statistically wrong. | Average raw nilai or weight by count. |
| `app/Filament/Pages/LaporanPembayaranPerTanggal.php:76,85` | `lainnya` bucket merges qris/VA; metode taxonomy inconsistent across reports. | Centralize metode enum + bucketing. |
| `app/Models/SettingGaji.php` (model) | No "one active setting per pegawai" enforcement; `SlipGajiForm:36` assumes single active. | Add activate()-style guard or partial unique. |
| `app/Models/SettingGaji.php:65-80` | Accessors add `decimal:2` strings, declare `float` → float-precision currency math. | `bcadd`/`bcsub` or money cast. |

---

## 🔵 LOW (~12)

| File:line | Problem | Fix |
|-----------|---------|-----|
| `app/Services/Rfid/PresensiScanService.php:74` | Existing row with `jam_masuk=null` (manual izin/sakit/alpha) routed to `updatePulangRecord` → overwrites non-attendance record. | Check `jam_masuk!==null`/status before treating as open masuk. |
| `app/Http/Controllers/Api/RfidScanController.php:124` | Always HTTP 200 even for tidak_dikenal/ditolak/duplikat (documented design; clients can't use status code). | Document firmware must parse `success`, or return 4xx for rejects. |
| `app/Models/KartuRfid.php:140-147` | `creating` auto-deactivation via raw `update()` fires no events / no activity log for restored duplicates. | Per-model saves if audit needed. |
| `app/Filament/Resources/KenaikanKelass/Schemas/KenaikanKelasForm.php:50` | `kelas_tujuan_id` can equal `kelas_asal_id`. | `->different('kelas_asal_id')`. |
| `app/Filament/Resources/BuktiTransfers/Schemas/BuktiTransferForm.php:36` | `tagihan_siswa_id` shows all students' bills regardless of chosen siswa. | Reactive filter on `siswa_id`. |
| `app/Filament/Resources/Pembayarans/Schemas/PembayaranForm.php:31-33` | `tagihan_siswa_id` filters out lunas/batal unconditionally → blank on edit if tagihan since became lunas. | Include currently-saved id on edit. |
| `app/Filament/Resources/SlipGajis/Schemas/SlipGajiForm.php:63` | `tahun` unbounded. | `->minValue(2000)->maxValue(date('Y')+1)`. |
| `app/Models/Pegawai.php:99-102` | `getNamaLengkapAttribute()` returns raw `nama` (no-op, misleading). | Remove or implement. |
| `app/Models/IzinKeluar.php:131-139` | `getDurasiAttribute()` mis-handles cross-midnight. | Compute against full datetimes or document same-day only. |
| `app/Models/Pegawai.php:113-121` | `getMasaKerjaAttribute()` ignores `tanggal_keluar` → overstates tenure for resigned staff. | `$end = $this->tanggal_keluar ?? now()`. |
| `app/Policies/RfidScanLogPolicy.php:22-37` | `replicate`/`reorder` undefined → safe now but inconsistent; shield regen could open replication. | Add explicit `false` methods. |
| `app/Policies/ActivityPolicy.php:21-65` | Audit log exposes create/update/replicate/reorder via shield → forgeable trail. | Harden to `false`. |
| `app/Filament/Resources/TabunganSiswas/Pages/EditTabunganSiswa.php` | (see C3) edit bypasses saldo recompute + sufficiency check. | Recompute affected rows on edit. |

---

## ✅ Confirmed correct (not bugs)
- `Pembayaran::applyAmountToTagihan` / `reconcileUpdatedPayment` — `lockForUpdate`+txn, correct reversal and old/new diffing (strongest code in repo).
- `InputAbsensi::simpan()` / `CreateAbsensi` / `EditAbsensi` — thorough cross-entity validation in a transaction.
- RFID device token hashed (bcrypt), `$hidden`, excluded from activity log; `verifyToken` uses `Hash::check` (timing-safe).
- UID normalization consistent across mutator/scope/service.
- Scan logs written on every path (solid audit trail).
- Date filters use `whereBetween` on true `date` columns — no datetime off-by-one.
- `User` fillable minimal (no privilege fields mass-assignable).
- **Retracted:** bearer token is NOT logged — `$request->all()` reads body only, header token never captured.

---

## Suggested fix order
1. **🔴 C1–C7** — each is data corruption / money / books-don't-balance.
2. **🟠 RFID+security batch** (H1–H6, H3, H4) — small, high-value, isolated.
3. **🟠 Accounting** (H11–H16) + **C6/C7** — needs the kas→ledger posting design decision first.
4. **🟠 forms/HR** (H17–H18) + 🟡 validation gaps — mechanical.
5. **🔵** — cleanup.

Every fix ships with a Pest test (`php artisan test --compact --filter=...`) and `vendor/bin/pint --dirty` per CLAUDE.md.
