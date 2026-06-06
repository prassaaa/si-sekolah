<?php

namespace App\Services\Accounting;

use App\Models\Akun;
use App\Models\JurnalUmum;
use Illuminate\Support\Carbon;

class FinancialService
{
    /**
     * Total pendapatan (revenue) for a period, derived from the general ledger.
     *
     * Revenue accounts have a normal credit balance, so the period total is
     * SUM(kredit) - SUM(debit) across all akun with tipe = 'pendapatan'.
     */
    public function totalPendapatan(Carbon|string $start, Carbon|string $end): string
    {
        return $this->sumByTipe('pendapatan', $start, $end, credit: true);
    }

    /**
     * Total beban (expense) for a period, derived from the general ledger.
     *
     * Expense accounts have a normal debit balance, so the period total is
     * SUM(debit) - SUM(kredit) across all akun with tipe = 'beban'.
     */
    public function totalBeban(Carbon|string $start, Carbon|string $end): string
    {
        return $this->sumByTipe('beban', $start, $end, credit: false);
    }

    /**
     * Canonical net income (laba/rugi bersih) for a period: pendapatan - beban.
     *
     * This is the single source of truth reused by LabaRugi, PerubahanModal,
     * and FinancialOverview so all three pages always agree.
     */
    public function netIncome(Carbon|string $start, Carbon|string $end): string
    {
        return bcsub(
            $this->totalPendapatan($start, $end),
            $this->totalBeban($start, $end),
            2,
        );
    }

    /**
     * Sum the ledger movement for every account of a given tipe over a period.
     */
    private function sumByTipe(string $tipe, Carbon|string $start, Carbon|string $end, bool $credit): string
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->endOfDay();

        $akunIds = Akun::query()->where('tipe', $tipe)->pluck('id');

        if ($akunIds->isEmpty()) {
            return '0.00';
        }

        $row = JurnalUmum::query()
            ->whereIn('akun_id', $akunIds)
            ->whereBetween('tanggal', [$start, $end])
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(kredit), 0) as total_kredit')
            ->first();

        $debit = (string) ($row->total_debit ?? 0);
        $kredit = (string) ($row->total_kredit ?? 0);

        return $credit
            ? bcsub($kredit, $debit, 2)
            : bcsub($debit, $kredit, 2);
    }
}
