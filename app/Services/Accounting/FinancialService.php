<?php

namespace App\Services\Accounting;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\SaldoAwal;
use Illuminate\Support\Carbon;

class FinancialService
{
    /**
     * Total pendapatan (revenue) for a period, derived from the general ledger.
     *
     * Revenue accounts have a normal credit balance, so the period total is
     * SUM(kredit) - SUM(debit) across all akun with tipe = 'pendapatan'.
     */
    public function totalPendapatan(Carbon|string|null $start, Carbon|string $end): string
    {
        return $this->sumByTipe('pendapatan', $start, $end, credit: true);
    }

    /**
     * Total beban (expense) for a period, derived from the general ledger.
     *
     * Expense accounts have a normal debit balance, so the period total is
     * SUM(debit) - SUM(kredit) across all akun with tipe = 'beban'.
     */
    public function totalBeban(Carbon|string|null $start, Carbon|string $end): string
    {
        return $this->sumByTipe('beban', $start, $end, credit: false);
    }

    /**
     * Canonical net income (laba/rugi bersih) for a period: pendapatan - beban.
     *
     * This is the single source of truth reused by LabaRugi, PerubahanModal,
     * Neraca, and FinancialOverview so all pages always agree. A null $start
     * means "since the beginning of the books" (no lower bound), used by Neraca
     * to compute laba berjalan from the latest saldo awal snapshot date.
     */
    public function netIncome(Carbon|string|null $start, Carbon|string $end): string
    {
        return bcsub(
            $this->totalPendapatan($start, $end),
            $this->totalBeban($start, $end),
            2,
        );
    }

    /**
     * Snapshot of saldo awal per akun as of a reference date.
     *
     * For each akun this returns the SINGLE saldo_awals row whose `tanggal` is
     * the largest value <= $perTanggal (NOT a SUM across tahun ajaran). A saldo
     * awal of a later tahun ajaran is a carry-over of the prior period's closing
     * balance, so summing them double-counts. Picking the latest snapshot avoids
     * that. When $perTanggal is null, the latest snapshot of all time is used.
     *
     * Implemented as one window-style query (a correlated subquery selecting the
     * max tanggal per akun), avoiding the N+1 of one query per account.
     *
     * @return array<int, array{saldo: string, tanggal: string}>
     */
    public function saldoAwalSnapshotPerAkun(Carbon|string|null $perTanggal = null): array
    {
        $tanggal = $perTanggal !== null
            ? Carbon::parse($perTanggal)->toDateString()
            : null;

        $rows = SaldoAwal::query()
            ->select('saldo_awals.akun_id', 'saldo_awals.saldo', 'saldo_awals.tanggal')
            ->whereNull('saldo_awals.deleted_at')
            ->when($tanggal, fn ($q) => $q->whereDate('saldo_awals.tanggal', '<=', $tanggal))
            ->whereRaw(
                'DATE(saldo_awals.tanggal) = ('.
                'SELECT MAX(DATE(inner_sa.tanggal)) FROM saldo_awals AS inner_sa '.
                'WHERE inner_sa.akun_id = saldo_awals.akun_id '.
                'AND inner_sa.deleted_at IS NULL'.
                ($tanggal !== null ? ' AND DATE(inner_sa.tanggal) <= ?' : '').
                ')',
                $tanggal !== null ? [$tanggal] : [],
            )
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->akun_id] = [
                'saldo' => (string) $row->saldo,
                'tanggal' => Carbon::parse($row->tanggal)->toDateString(),
            ];
        }

        return $result;
    }

    /**
     * The latest (largest) saldo awal snapshot date across the selected accounts
     * as of $perTanggal, or null when there is no snapshot.
     *
     * ASSUMPTION: saldo awal rows for a given tahun ajaran are entered on a single
     * shared date, so the max snapshot date is the date from which laba berjalan
     * should be accumulated. Returns 'Y-m-d' or null.
     *
     * @param  array<int>|null  $akunIds
     */
    public function latestSnapshotDate(Carbon|string|null $perTanggal = null, ?array $akunIds = null): ?string
    {
        $snapshot = $this->saldoAwalSnapshotPerAkun($perTanggal);

        if ($akunIds !== null) {
            $snapshot = array_intersect_key($snapshot, array_flip($akunIds));
        }

        if ($snapshot === []) {
            return null;
        }

        $dates = array_map(fn (array $row): string => $row['tanggal'], $snapshot);

        return max($dates);
    }

    /**
     * Balance per account as of a reference date (inclusive), using snapshot
     * semantics.
     *
     * For each requested account the balance is the saldo awal snapshot (latest
     * saldo_awals row with tanggal <= $perTanggal) plus the ledger movement from
     * that snapshot date through $perTanggal (jurnal tanggal >= snapshot.tanggal
     * AND <= $perTanggal), in the direction of the account's posisi_normal. When
     * an account has no snapshot, all jurnal up to $perTanggal are summed.
     *
     * @param  array<int>|null  $akunIds  Restrict to these akun ids; null = all akun.
     * @return array<int, string>
     */
    public function saldoPerAkun(?array $akunIds, Carbon|string $perTanggal): array
    {
        return $this->hitungSaldoPerAkun($akunIds, $perTanggal, inklusif: true);
    }

    /**
     * Opening balance per account at the START of a period (exclusive upper
     * bound), using snapshot semantics.
     *
     * The snapshot reference is INCLUSIVE of $tanggalMulai (a saldo awal dated
     * exactly on the period start is that period's opening balance), while the
     * journal movement added on top is strictly BEFORE $tanggalMulai (jurnal
     * tanggal >= snapshot.tanggal AND < $tanggalMulai). This is the semantics
     * used by BukuBesar's "Saldo Awal" row and PerubahanModal's "Modal Awal",
     * which makes Modal Akhir of one period chain into Modal Awal of the next.
     *
     * @param  array<int>|null  $akunIds  Restrict to these akun ids; null = all akun.
     * @return array<int, string>
     */
    public function saldoAwalPeriodePerAkun(?array $akunIds, Carbon|string $tanggalMulai): array
    {
        return $this->hitungSaldoPerAkun($akunIds, $tanggalMulai, inklusif: false);
    }

    /**
     * Shared core for saldoPerAkun / saldoAwalPeriodePerAkun.
     *
     * @param  array<int>|null  $akunIds
     * @return array<int, string>
     */
    private function hitungSaldoPerAkun(?array $akunIds, Carbon|string $perTanggal, bool $inklusif): array
    {
        $tanggal = Carbon::parse($perTanggal)->toDateString();

        $akuns = Akun::query()
            ->when($akunIds !== null, fn ($q) => $q->whereIn('id', $akunIds))
            ->get(['id', 'posisi_normal']);

        $snapshot = $this->saldoAwalSnapshotPerAkun($tanggal);

        $result = [];
        foreach ($akuns as $akun) {
            $isDebitNormal = $akun->posisi_normal === 'debit';
            $snap = $snapshot[$akun->id] ?? null;
            $awal = $snap['saldo'] ?? '0';

            $row = JurnalUmum::query()
                ->where('akun_id', $akun->id)
                ->when($snap !== null, fn ($q) => $q->whereDate('tanggal', '>=', $snap['tanggal']))
                ->whereDate('tanggal', $inklusif ? '<=' : '<', $tanggal)
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(kredit), 0) as total_kredit')
                ->first();

            $debit = (string) ($row->total_debit ?? '0');
            $kredit = (string) ($row->total_kredit ?? '0');

            $jurnalSaldo = $isDebitNormal
                ? bcsub($debit, $kredit, 2)
                : bcsub($kredit, $debit, 2);

            $result[$akun->id] = bcadd((string) $awal, $jurnalSaldo, 2);
        }

        return $result;
    }

    /**
     * Sum the ledger movement for every account of a given tipe over a period.
     *
     * A null $start removes the lower bound (since the beginning of the books).
     */
    private function sumByTipe(string $tipe, Carbon|string|null $start, Carbon|string $end, bool $credit): string
    {
        $start = $start !== null ? Carbon::parse($start)->startOfDay() : null;
        $end = Carbon::parse($end)->endOfDay();

        $akunIds = Akun::query()->where('tipe', $tipe)->pluck('id');

        if ($akunIds->isEmpty()) {
            return '0.00';
        }

        $row = JurnalUmum::query()
            ->whereIn('akun_id', $akunIds)
            ->when($start !== null, fn ($q) => $q->where('tanggal', '>=', $start))
            ->where('tanggal', '<=', $end)
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(kredit), 0) as total_kredit')
            ->first();

        $debit = (string) ($row->total_debit ?? 0);
        $kredit = (string) ($row->total_kredit ?? 0);

        return $credit
            ? bcsub($kredit, $debit, 2)
            : bcsub($debit, $kredit, 2);
    }
}
