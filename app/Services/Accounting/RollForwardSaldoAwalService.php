<?php

namespace App\Services\Accounting;

use App\Models\Akun;
use App\Models\SaldoAwal;
use App\Models\TahunAjaran;
use Illuminate\Support\Facades\DB;

/**
 * Roll-forward saldo awal antar tahun ajaran (temuan #55 / F3).
 *
 * Menutup buku TA lama dan membentuk saldo awal TA baru secara otomatis:
 *
 * 1. Akun RIIL (aset/liabilitas/ekuitas) — saldo akhir per
 *    `taLama->tanggal_selesai` (via FinancialService::saldoPerAkun yang sudah
 *    memuat snapshot saldo awal + pergerakan jurnal) menjadi saldo awal TA baru
 *    bertanggal `taBaru->tanggal_mulai`.
 *
 * 2. Akun NOMINAL (pendapatan/beban) — TIDAK dibawa sebagai saldo awal. Akun
 *    sementara ini ditutup ke Laba Ditahan: saldo awal Laba Ditahan (3-2001) TA
 *    baru = saldo akhir Laba Ditahan TA lama + laba bersih (netIncome) sepanjang
 *    TA lama. Dengan begitu efek pendapatan/beban (yang sudah tercermin pada
 *    saldo aset/liabilitas) terserap ke ekuitas dan Neraca TA baru tetap
 *    SEIMBANG: Σaset = Σliabilitas + Σekuitas_lain + (LabaDitahan + labaBersih).
 *
 * Idempoten: insert memakai updateOrCreate berdasarkan unique
 * (akun_id, tahun_ajaran_id). Menjalankan ulang akan MENYEGARKAN nilai (mis.
 * setelah jurnal TA lama dikoreksi) tanpa menggandakan baris — bukan skip —
 * sehingga saldo awal TA baru selalu konsisten dengan pembukuan TA lama.
 */
class RollForwardSaldoAwalService
{
    /**
     * Kode akun Laba Ditahan tempat laba/rugi TA lama ditutup.
     */
    private const KODE_LABA_DITAHAN = '3-2001';

    /**
     * Tipe akun riil yang saldo akhirnya dibawa menjadi saldo awal TA baru.
     *
     * @var array<int, string>
     */
    private const TIPE_RIIL = ['aset', 'liabilitas', 'ekuitas'];

    public function __construct(private FinancialService $financialService) {}

    /**
     * Generate saldo awal TA baru dari saldo akhir TA lama.
     *
     * @return array{akun_diproses: int, laba_ditahan_ditambah: string}
     */
    public function generate(TahunAjaran $taLama, TahunAjaran $taBaru): array
    {
        $tanggalSelesaiLama = $taLama->tanggal_selesai->toDateString();
        $tanggalMulaiBaru = $taBaru->tanggal_mulai->toDateString();

        return DB::transaction(function () use ($taLama, $taBaru, $tanggalSelesaiLama, $tanggalMulaiBaru): array {
            $akunRiil = Akun::query()
                ->whereIn('tipe', self::TIPE_RIIL)
                ->get(['id', 'kode']);

            $akunIds = $akunRiil->pluck('id')->all();
            $saldoAkhir = $this->financialService->saldoPerAkun($akunIds, $tanggalSelesaiLama);

            $labaDitahan = $akunRiil->firstWhere('kode', self::KODE_LABA_DITAHAN);
            $labaBersih = $this->financialService->netIncome(
                $taLama->tanggal_mulai->toDateString(),
                $tanggalSelesaiLama,
            );

            $akunDiproses = 0;

            foreach ($akunRiil as $akun) {
                $saldo = $saldoAkhir[$akun->id] ?? '0.00';

                if ($labaDitahan !== null && $akun->id === $labaDitahan->id) {
                    $saldo = bcadd($saldo, $labaBersih, 2);
                }

                $this->simpanSaldoAwal($akun->id, $taBaru->id, $saldo, $tanggalMulaiBaru, $taLama->kode);

                $akunDiproses++;
            }

            return [
                'akun_diproses' => $akunDiproses,
                'laba_ditahan_ditambah' => $labaBersih,
            ];
        });
    }

    /**
     * Simpan (insert/replace) satu baris saldo awal untuk akun + TA baru.
     *
     * Idempoten via unique (akun_id, tahun_ajaran_id). Unique index pada
     * saldo_awals tidak menyertakan deleted_at (temuan #94), sehingga baris yang
     * sudah di-soft-delete tetap menempati index. Maka pencarian dilakukan dengan
     * withTrashed() lalu baris dipulihkan agar tidak terjadi tabrakan unique saat
     * generate diulang setelah pernah dihapus.
     */
    private function simpanSaldoAwal(int $akunId, int $tahunAjaranId, string $saldo, string $tanggal, string $kodeTaLama): void
    {
        $existing = SaldoAwal::withTrashed()
            ->where('akun_id', $akunId)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->first();

        $atribut = [
            'saldo' => $saldo,
            'tanggal' => $tanggal,
            'keterangan' => "Roll-forward dari {$kodeTaLama}",
        ];

        if ($existing !== null) {
            $existing->forceFill([...$atribut, 'deleted_at' => null])->save();

            return;
        }

        SaldoAwal::create([
            'akun_id' => $akunId,
            'tahun_ajaran_id' => $tahunAjaranId,
            ...$atribut,
        ]);
    }
}
