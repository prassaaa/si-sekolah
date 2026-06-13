<?php

namespace App\Services\Sarpras;

use App\Models\JurnalUmum;
use App\Models\SarprasBarang;
use App\Models\SarprasPengadaan;
use App\Models\SarprasPengadaanItem;
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
