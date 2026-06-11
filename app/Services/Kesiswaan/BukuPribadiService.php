<?php

namespace App\Services\Kesiswaan;

use App\Models\Sekolah;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class BukuPribadiService
{
    /**
     * Kumpulkan semua data siswa untuk cetak buku pribadi.
     *
     * @return array{
     *     siswa: Siswa,
     *     sekolah: Sekolah|null,
     *     konselings: Collection,
     *     pelanggarans: Collection,
     *     total_poin: int,
     *     prestasis: Collection,
     *     tahfidzs: Collection,
     *     presensi_rekap: array<string, int>,
     * }
     */
    public function data(Siswa $siswa): array
    {
        $siswa->loadMissing([
            'kelas',
            'konselings.konselor',
            'konselings.semester',
            'pelanggarans.semester',
            'prestasis.semester',
            'tahfidzs.semester',
        ]);

        /** @var array<string, int> $presensiRekap */
        $presensiRekap = $siswa->presensiHarians()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status')
            ->toArray();

        $totalPoin = (int) $siswa->pelanggarans->sum('poin');

        return [
            'siswa' => $siswa,
            'sekolah' => Sekolah::query()->first(),
            'konselings' => $siswa->konselings,
            'pelanggarans' => $siswa->pelanggarans,
            'total_poin' => $totalPoin,
            'prestasis' => $siswa->prestasis,
            'tahfidzs' => $siswa->tahfidzs,
            'presensi_rekap' => $presensiRekap,
        ];
    }

    /**
     * Buat PDF buku pribadi siswa.
     */
    public function pdf(Siswa $siswa): DomPDF
    {
        return Pdf::loadView('exports.buku-pribadi', $this->data($siswa))
            ->setPaper('a4', 'portrait');
    }

    /**
     * Nama file unduhan PDF.
     */
    public function filename(Siswa $siswa): string
    {
        return 'buku-pribadi-'.Str::slug($siswa->nis.' '.$siswa->nama).'.pdf';
    }
}
