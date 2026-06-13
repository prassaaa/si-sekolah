<?php

namespace App\Services\Sarpras;

use App\Models\JurnalUmum;
use App\Models\SarprasBarang;
use App\Models\SarprasPemeliharaan;
use App\Models\SarprasPeminjaman;
use App\Models\SarprasPengadaan;
use App\Models\SarprasPengadaanItem;
use App\Models\SarprasPenghapusan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Posts balanced double-entry journal rows to jurnal_umums for sarpras events:
 * procurement intake and monthly depreciation.
 *
 * Accounts are resolved by convention via AkunResolver. If any required
 * account cannot be resolved, posting is SKIPPED with a Log::warning — never
 * guessed, never throws (lesson from audit C6). Stock intake / report math is
 * therefore never broken by a missing chart-of-accounts.
 *
 * All posting is idempotent: rows are tagged with jenis_referensi +
 * referensi_id (and, for depreciation, a periode marker in referensi) so they
 * are never double-posted and can be reversed.
 */
class SarprasJournalPoster
{
    public const JENIS_PENGADAAN = 'sarpras_pengadaan';

    public const JENIS_PENYUSUTAN = 'sarpras_penyusutan';

    public const JENIS_PENGHAPUSAN = 'sarpras_penghapusan';

    public const JENIS_PEMELIHARAAN = 'sarpras_pemeliharaan';

    public const JENIS_DENDA = 'sarpras_denda';

    public function __construct(
        private AkunResolver $akun,
        private PenyusutanService $penyusutan,
    ) {}

    /**
     * Post procurement intake: debit Perlengkapan (bahan) atau Aset Tetap (aset)
     * per tipe barang yang dihasilkan, credit Kas/Bank total.
     *
     * Setiap item dijurnal berdasarkan tipe SarprasBarang yang terbentuk saat
     * terima(): tipe='bahan' → debit Perlengkapan (1-3001); tipe='aset' → debit
     * Aset Tetap (1-4001). Total kredit Kas = total seluruh item agar tetap balance.
     * Idempotent per pengadaan. Returns true when a journal was posted.
     */
    public function postPengadaan(SarprasPengadaan $pengadaan): bool
    {
        $total = (string) $pengadaan->total_biaya;

        if (bccomp($total, '0', 2) <= 0) {
            return false;
        }

        $kreditAkunId = $this->akun->kasAkunId();

        if ($kreditAkunId === null) {
            Log::warning('Sarpras pengadaan journal skipped: akun kas tidak ditemukan.', [
                'jenis_referensi' => self::JENIS_PENGADAAN,
                'referensi_id' => $pengadaan->getKey(),
            ]);

            return false;
        }

        if ($this->alreadyPostedPengadaan($pengadaan)) {
            return false;
        }

        /** @var list<array{akun_id: int, subtotal: string, label: string}> $debitLines */
        $debitLines = $this->buildDebitLines($pengadaan);

        if (empty($debitLines)) {
            Log::warning('Sarpras pengadaan journal skipped: tidak ada baris debit yang dapat diposting.', [
                'jenis_referensi' => self::JENIS_PENGADAAN,
                'referensi_id' => $pengadaan->getKey(),
            ]);

            return false;
        }

        $nomor = $pengadaan->nomor;
        $keterangan = 'Pengadaan sarpras '.$nomor;

        DB::transaction(function () use ($pengadaan, $debitLines, $kreditAkunId, $total, $nomor, $keterangan): void {
            $token = $this->freshToken();
            $seq = 1;

            foreach ($debitLines as $line) {
                JurnalUmum::create([
                    'nomor_bukti' => $nomor.'-D'.$seq.'-'.$token,
                    'tanggal' => $pengadaan->tanggal,
                    'keterangan' => $keterangan.' ('.$line['label'].')',
                    'akun_id' => $line['akun_id'],
                    'debit' => $line['subtotal'],
                    'kredit' => '0',
                    'referensi' => $nomor,
                    'jenis_referensi' => self::JENIS_PENGADAAN,
                    'referensi_id' => $pengadaan->getKey(),
                    'created_by' => $pengadaan->dibuat_oleh,
                ]);
                $seq++;
            }

            JurnalUmum::create([
                'nomor_bukti' => $nomor.'-K-'.$token,
                'tanggal' => $pengadaan->tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $kreditAkunId,
                'debit' => '0',
                'kredit' => $total,
                'referensi' => $nomor,
                'jenis_referensi' => self::JENIS_PENGADAAN,
                'referensi_id' => $pengadaan->getKey(),
                'created_by' => $pengadaan->dibuat_oleh,
            ]);
        });

        return true;
    }

    /**
     * Bangun daftar baris debit untuk postPengadaan(), dikelompokkan per akun
     * berdasarkan tipe SarprasBarang yang terbentuk dari tiap item.
     * Tipe 'bahan' → Perlengkapan; tipe 'aset' → Aset Tetap.
     *
     * @return list<array{akun_id: int, subtotal: string, label: string}>
     */
    private function buildDebitLines(SarprasPengadaan $pengadaan): array
    {
        $asetTetapAkunId = $this->akun->asetTetapAkunId();
        $perlengkapanAkunId = $this->akun->perlengkapanAkunId();

        /** @var array<int, string> $totalsPerAkun akun_id → bc-math accumulated subtotal */
        $totalsPerAkun = [];
        /** @var array<int, string> $labelPerAkun akun_id → label string */
        $labelPerAkun = [];

        foreach ($pengadaan->items()->get() as $item) {
            /** @var SarprasPengadaanItem $item */
            $kodeInventaris = 'INV-'.$pengadaan->nomor.'-'.$item->getKey();

            $barang = SarprasBarang::query()
                ->where('kode_inventaris', $kodeInventaris)
                ->first();

            $tipe = $barang?->tipe ?? 'bahan';

            if ($tipe === 'aset') {
                $akunId = $asetTetapAkunId;
                $label = 'Aset Tetap';
            } else {
                $akunId = $perlengkapanAkunId;
                $label = 'Perlengkapan';
            }

            if ($akunId === null) {
                Log::warning('Sarpras pengadaan: akun tidak ditemukan untuk item, item dilewati.', [
                    'item_id' => $item->getKey(),
                    'tipe' => $tipe,
                    'kode_inventaris' => $kodeInventaris,
                ]);

                continue;
            }

            $subtotal = (string) $item->subtotal;
            $totalsPerAkun[$akunId] = isset($totalsPerAkun[$akunId])
                ? bcadd($totalsPerAkun[$akunId], $subtotal, 2)
                : $subtotal;
            $labelPerAkun[$akunId] = $label;
        }

        $lines = [];
        foreach ($totalsPerAkun as $akunId => $subtotal) {
            if (bccomp($subtotal, '0', 2) > 0) {
                $lines[] = [
                    'akun_id' => $akunId,
                    'subtotal' => $subtotal,
                    'label' => $labelPerAkun[$akunId],
                ];
            }
        }

        return $lines;
    }

    /**
     * Reverse (soft-delete) any procurement journal rows for a pengadaan.
     */
    public function reversePengadaan(SarprasPengadaan $pengadaan): void
    {
        JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS_PENGADAAN)
            ->where('referensi_id', $pengadaan->getKey())
            ->delete();
    }

    /**
     * Post monthly depreciation for one asset for the given period: debit Beban
     * Penyusutan, credit Akumulasi Penyusutan. Idempotent per (barang, periode)
     * via the periode marker in `referensi`. Returns true when posted.
     */
    public function postPenyusutan(SarprasBarang $barang, Carbon $periode): bool
    {
        if (! $barang->isDepreciable()) {
            return false;
        }

        $akhirPeriode = $periode->copy()->endOfMonth();
        $nominal = $this->penyusutan->penyusutanPerBulan($barang);

        if (bccomp($nominal, '0', 2) <= 0) {
            return false;
        }

        // Do not depreciate beyond the depreciable base.
        $base = bcsub(
            (string) $barang->harga_perolehan,
            (string) $barang->nilai_residu,
            2,
        );
        $sudahDisusut = $this->penyusutan->akumulasiSampai($barang, $periode->copy()->subMonthNoOverflow()->endOfMonth());

        if (bccomp($sudahDisusut, $base, 2) >= 0) {
            return false;
        }

        $sisa = bcsub($base, $sudahDisusut, 2);
        if (bccomp($nominal, $sisa, 2) > 0) {
            $nominal = $sisa;
        }

        $debitAkunId = $this->akun->bebanPenyusutanAkunId();
        $kreditAkunId = $this->akun->akumulasiPenyusutanAkunId();

        $periodeMarker = $periode->format('Y-m');

        if ($debitAkunId === null || $kreditAkunId === null) {
            Log::warning('Sarpras penyusutan journal skipped: required akun not resolved.', [
                'jenis_referensi' => self::JENIS_PENYUSUTAN,
                'referensi_id' => $barang->getKey(),
                'periode' => $periodeMarker,
                'beban' => $debitAkunId,
                'akumulasi' => $kreditAkunId,
            ]);

            return false;
        }

        if ($this->alreadyPostedPenyusutan($barang, $periodeMarker)) {
            return false;
        }

        $referensi = 'SUSUT-'.$periodeMarker;
        $keterangan = 'Penyusutan '.$barang->nama.' periode '.$periodeMarker;

        DB::transaction(function () use ($barang, $debitAkunId, $kreditAkunId, $nominal, $referensi, $keterangan, $akhirPeriode): void {
            $token = $this->freshToken();

            JurnalUmum::create([
                'nomor_bukti' => $referensi.'-'.$barang->getKey().'-D-'.$token,
                'tanggal' => $akhirPeriode,
                'keterangan' => $keterangan,
                'akun_id' => $debitAkunId,
                'debit' => $nominal,
                'kredit' => '0',
                'referensi' => $referensi,
                'jenis_referensi' => self::JENIS_PENYUSUTAN,
                'referensi_id' => $barang->getKey(),
                'created_by' => null,
            ]);

            JurnalUmum::create([
                'nomor_bukti' => $referensi.'-'.$barang->getKey().'-K-'.$token,
                'tanggal' => $akhirPeriode,
                'keterangan' => $keterangan,
                'akun_id' => $kreditAkunId,
                'debit' => '0',
                'kredit' => $nominal,
                'referensi' => $referensi,
                'jenis_referensi' => self::JENIS_PENYUSUTAN,
                'referensi_id' => $barang->getKey(),
                'created_by' => null,
            ]);
        });

        return true;
    }

    /**
     * Post asset write-off when a penghapusan is approved (status 'disetujui').
     *
     * Double-entry mirrors disposal accounting at book value:
     *   - Kredit Aset Tetap (1-4001)        sebesar harga perolehan
     *   - Debit Akumulasi Penyusutan (1-4002) sebesar akumulasi tercatat
     *   - Debit Kerugian Penghapusan (5-5002) sebesar nilai buku tersisa
     *
     * Akumulasi/nilai buku diambil dari PenyusutanService per tanggal
     * penghapusan untuk aset yang dapat disusutkan; untuk aset non-susut
     * akumulasi = 0 sehingga seluruh harga perolehan menjadi kerugian. Total
     * debit selalu sama dengan kredit (akumulasi + kerugian = perolehan).
     *
     * Idempotent per penghapusan, di-gate cut-off (tanggal penghapusan), dan
     * dilewati dengan Log::warning bila salah satu akun tidak ter-resolve.
     * Returns true when a journal was posted.
     */
    public function postPenghapusan(SarprasPenghapusan $penghapusan): bool
    {
        if (! $this->isAfterCutoff($penghapusan->tanggal)) {
            return false;
        }

        $barang = $penghapusan->barang;

        if ($barang === null) {
            return false;
        }

        $perolehan = $this->money($barang->harga_perolehan);

        if (bccomp($perolehan, '0', 2) <= 0) {
            return false;
        }

        $asetTetapAkunId = $this->akun->asetTetapAkunId();
        $akumulasiAkunId = $this->akun->akumulasiPenyusutanAkunId();
        $kerugianAkunId = $this->akun->kerugianPenghapusanId();

        if ($asetTetapAkunId === null || $akumulasiAkunId === null || $kerugianAkunId === null) {
            Log::warning('Sarpras penghapusan journal skipped: required akun not resolved.', [
                'jenis_referensi' => self::JENIS_PENGHAPUSAN,
                'referensi_id' => $penghapusan->getKey(),
                'aset_tetap' => $asetTetapAkunId,
                'akumulasi' => $akumulasiAkunId,
                'kerugian' => $kerugianAkunId,
            ]);

            return false;
        }

        if ($this->alreadyPostedPenghapusan($penghapusan)) {
            return false;
        }

        $tanggal = Carbon::parse($penghapusan->tanggal);

        $akumulasi = $barang->isDepreciable()
            ? $this->penyusutan->akumulasiSampai($barang, $tanggal->copy())
            : '0.00';

        if (bccomp($akumulasi, $perolehan, 2) > 0) {
            $akumulasi = $perolehan;
        }

        $kerugian = bcsub($perolehan, $akumulasi, 2);

        $nomor = $penghapusan->nomor;
        $keterangan = 'Penghapusan aset '.$barang->nama.' ('.$nomor.')';

        DB::transaction(function () use ($penghapusan, $tanggal, $nomor, $keterangan, $asetTetapAkunId, $akumulasiAkunId, $kerugianAkunId, $perolehan, $akumulasi, $kerugian): void {
            $token = $this->freshToken();
            $createdBy = $penghapusan->disetujui_oleh;

            JurnalUmum::create([
                'nomor_bukti' => $nomor.'-K-'.$token,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $asetTetapAkunId,
                'debit' => '0',
                'kredit' => $perolehan,
                'referensi' => $nomor,
                'jenis_referensi' => self::JENIS_PENGHAPUSAN,
                'referensi_id' => $penghapusan->getKey(),
                'created_by' => $createdBy,
            ]);

            if (bccomp($akumulasi, '0', 2) > 0) {
                JurnalUmum::create([
                    'nomor_bukti' => $nomor.'-DA-'.$token,
                    'tanggal' => $tanggal,
                    'keterangan' => $keterangan.' (akumulasi penyusutan)',
                    'akun_id' => $akumulasiAkunId,
                    'debit' => $akumulasi,
                    'kredit' => '0',
                    'referensi' => $nomor,
                    'jenis_referensi' => self::JENIS_PENGHAPUSAN,
                    'referensi_id' => $penghapusan->getKey(),
                    'created_by' => $createdBy,
                ]);
            }

            if (bccomp($kerugian, '0', 2) > 0) {
                JurnalUmum::create([
                    'nomor_bukti' => $nomor.'-DK-'.$token,
                    'tanggal' => $tanggal,
                    'keterangan' => $keterangan.' (kerugian penghapusan)',
                    'akun_id' => $kerugianAkunId,
                    'debit' => $kerugian,
                    'kredit' => '0',
                    'referensi' => $nomor,
                    'jenis_referensi' => self::JENIS_PENGHAPUSAN,
                    'referensi_id' => $penghapusan->getKey(),
                    'created_by' => $createdBy,
                ]);
            }
        });

        return true;
    }

    /**
     * Reverse (soft-delete) any write-off journal rows for a penghapusan.
     */
    public function reversePenghapusan(SarprasPenghapusan $penghapusan): void
    {
        JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS_PENGHAPUSAN)
            ->where('referensi_id', $penghapusan->getKey())
            ->delete();
    }

    /**
     * Post maintenance expense when a pemeliharaan completes (status 'selesai')
     * with biaya > 0. Cash basis:
     *   - Debit Beban Pemeliharaan (5-3003) / Kredit Kas (1-1001), nominal = biaya.
     *
     * Idempotent per pemeliharaan, di-gate cut-off (tanggal_selesai bila ada,
     * jika tidak tanggal), dilewati dengan Log::warning bila akun tidak
     * ter-resolve. Returns true when a journal was posted.
     */
    public function postPemeliharaan(SarprasPemeliharaan $pemeliharaan): bool
    {
        $tanggalRujukan = $pemeliharaan->tanggal_selesai ?? $pemeliharaan->tanggal;

        if (! $this->isAfterCutoff($tanggalRujukan)) {
            return false;
        }

        $biaya = $this->money($pemeliharaan->biaya);

        if (bccomp($biaya, '0', 2) <= 0) {
            return false;
        }

        $bebanAkunId = $this->akun->bebanPemeliharaanId();
        $kasAkunId = $this->akun->kasDefaultId();

        if ($bebanAkunId === null || $kasAkunId === null) {
            Log::warning('Sarpras pemeliharaan journal skipped: required akun not resolved.', [
                'jenis_referensi' => self::JENIS_PEMELIHARAAN,
                'referensi_id' => $pemeliharaan->getKey(),
                'beban' => $bebanAkunId,
                'kas' => $kasAkunId,
            ]);

            return false;
        }

        if ($this->alreadyPostedPemeliharaan($pemeliharaan)) {
            return false;
        }

        $tanggal = Carbon::parse($tanggalRujukan);
        $nomor = $pemeliharaan->nomor;
        $keterangan = 'Biaya pemeliharaan '.$nomor;

        DB::transaction(function () use ($pemeliharaan, $tanggal, $nomor, $keterangan, $bebanAkunId, $kasAkunId, $biaya): void {
            $token = $this->freshToken();
            $createdBy = $pemeliharaan->dicatat_oleh;

            JurnalUmum::create([
                'nomor_bukti' => $nomor.'-D-'.$token,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $bebanAkunId,
                'debit' => $biaya,
                'kredit' => '0',
                'referensi' => $nomor,
                'jenis_referensi' => self::JENIS_PEMELIHARAAN,
                'referensi_id' => $pemeliharaan->getKey(),
                'created_by' => $createdBy,
            ]);

            JurnalUmum::create([
                'nomor_bukti' => $nomor.'-K-'.$token,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $kasAkunId,
                'debit' => '0',
                'kredit' => $biaya,
                'referensi' => $nomor,
                'jenis_referensi' => self::JENIS_PEMELIHARAAN,
                'referensi_id' => $pemeliharaan->getKey(),
                'created_by' => $createdBy,
            ]);
        });

        return true;
    }

    /**
     * Reverse (soft-delete) any maintenance journal rows for a pemeliharaan.
     */
    public function reversePemeliharaan(SarprasPemeliharaan $pemeliharaan): void
    {
        JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS_PEMELIHARAAN)
            ->where('referensi_id', $pemeliharaan->getKey())
            ->delete();
    }

    /**
     * Post late-return fine income when a peminjaman is returned (status
     * 'dikembalikan'/'terlambat') with denda > 0. Cash basis:
     *   - Debit Kas (1-1001) / Kredit Pendapatan Denda (4-1006), nominal = denda.
     *
     * Asumsi pengakuan: denda diakui (kas masuk) pada saat pengembalian
     * tercatat — lifecycle peminjaman tidak punya status 'terbayar' terpisah,
     * sehingga tanggal_kembali dipakai sebagai tanggal pengakuan. Idempotent per
     * peminjaman, di-gate cut-off (tanggal_kembali), dilewati dengan
     * Log::warning bila akun tidak ter-resolve. Returns true when posted.
     */
    public function postDenda(SarprasPeminjaman $peminjaman): bool
    {
        $denda = $this->money($peminjaman->denda);

        if (bccomp($denda, '0', 2) <= 0) {
            return false;
        }

        $tanggalRujukan = $peminjaman->tanggal_kembali ?? $peminjaman->tanggal_pinjam;

        if (! $this->isAfterCutoff($tanggalRujukan)) {
            return false;
        }

        $kasAkunId = $this->akun->kasDefaultId();
        $pendapatanAkunId = $this->akun->pendapatanDendaId();

        if ($kasAkunId === null || $pendapatanAkunId === null) {
            Log::warning('Sarpras denda journal skipped: required akun not resolved.', [
                'jenis_referensi' => self::JENIS_DENDA,
                'referensi_id' => $peminjaman->getKey(),
                'kas' => $kasAkunId,
                'pendapatan' => $pendapatanAkunId,
            ]);

            return false;
        }

        if ($this->alreadyPostedDenda($peminjaman)) {
            return false;
        }

        $tanggal = Carbon::parse($tanggalRujukan);
        $nomor = $peminjaman->nomor;
        $keterangan = 'Denda keterlambatan peminjaman '.$nomor;

        DB::transaction(function () use ($peminjaman, $tanggal, $nomor, $keterangan, $kasAkunId, $pendapatanAkunId, $denda): void {
            $token = $this->freshToken();

            JurnalUmum::create([
                'nomor_bukti' => $nomor.'-DND-D-'.$token,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $kasAkunId,
                'debit' => $denda,
                'kredit' => '0',
                'referensi' => $nomor,
                'jenis_referensi' => self::JENIS_DENDA,
                'referensi_id' => $peminjaman->getKey(),
                'created_by' => null,
            ]);

            JurnalUmum::create([
                'nomor_bukti' => $nomor.'-DND-K-'.$token,
                'tanggal' => $tanggal,
                'keterangan' => $keterangan,
                'akun_id' => $pendapatanAkunId,
                'debit' => '0',
                'kredit' => $denda,
                'referensi' => $nomor,
                'jenis_referensi' => self::JENIS_DENDA,
                'referensi_id' => $peminjaman->getKey(),
                'created_by' => null,
            ]);
        });

        return true;
    }

    /**
     * Reverse (soft-delete) any fine journal rows for a peminjaman.
     */
    public function reverseDenda(SarprasPeminjaman $peminjaman): void
    {
        JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS_DENDA)
            ->where('referensi_id', $peminjaman->getKey())
            ->delete();
    }

    private function alreadyPostedPenghapusan(SarprasPenghapusan $penghapusan): bool
    {
        return JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS_PENGHAPUSAN)
            ->where('referensi_id', $penghapusan->getKey())
            ->exists();
    }

    private function alreadyPostedPemeliharaan(SarprasPemeliharaan $pemeliharaan): bool
    {
        return JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS_PEMELIHARAAN)
            ->where('referensi_id', $pemeliharaan->getKey())
            ->exists();
    }

    private function alreadyPostedDenda(SarprasPeminjaman $peminjaman): bool
    {
        return JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS_DENDA)
            ->where('referensi_id', $peminjaman->getKey())
            ->exists();
    }

    /**
     * Gate posting to transactions dated on/after the configured cut-off.
     * Mirrors TabunganJournalPoster: pre-cut-off events are pra-pembukuan
     * otomatis dan posisinya sudah diwakili saldo awal.
     */
    private function isAfterCutoff(mixed $tanggal): bool
    {
        if ($tanggal === null) {
            return false;
        }

        $cutoff = config('akuntansi.cutoff_posting');

        return Carbon::parse($tanggal)->gte(Carbon::parse($cutoff));
    }

    private function money(mixed $value): string
    {
        return bcadd((string) ($value ?? '0'), '0', 2);
    }

    private function alreadyPostedPengadaan(SarprasPengadaan $pengadaan): bool
    {
        return JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS_PENGADAAN)
            ->where('referensi_id', $pengadaan->getKey())
            ->exists();
    }

    private function alreadyPostedPenyusutan(SarprasBarang $barang, string $periodeMarker): bool
    {
        return JurnalUmum::query()
            ->where('jenis_referensi', self::JENIS_PENYUSUTAN)
            ->where('referensi_id', $barang->getKey())
            ->where('referensi', 'SUSUT-'.$periodeMarker)
            ->exists();
    }

    /**
     * jurnal_umums.nomor_bukti is globally unique and ignores soft-deletes, so
     * reposts after a reversal must use a fresh token.
     */
    private function freshToken(): int
    {
        return ((int) JurnalUmum::query()->withTrashed()->max('id')) + 1;
    }
}
