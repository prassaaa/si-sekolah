<?php

namespace App\Services\Accounting;

use App\Models\Sekolah;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;
use Illuminate\Support\Str;

/**
 * Render laporan keuangan tabular menjadi PDF formal (kop sekolah + tanda
 * tangan) memakai satu blade generik `exports.laporan-keuangan`.
 *
 * Pemakaian (fluent builder), mis. pada header action halaman Filament:
 *
 *   $pdf = LaporanPdfService::make()
 *       ->judul('NERACA')
 *       ->periode('Per 31 Desember 2026')
 *       ->kolom(['Kode', 'Nama Akun', ['Debit', 'right'], ['Kredit', 'right']])
 *       ->baris($rows)              // setiap baris = array sel sejajar urutan kolom
 *       ->ringkasan($totalRows)     // baris tebal di kaki tabel (opsional)
 *       ->render();
 *
 *   return response()->streamDownload(
 *       fn () => print ($pdf->output()),
 *       LaporanPdfService::make()->namaFile('neraca-2026-12-31'),
 *   );
 */
class LaporanPdfService
{
    private string $judul = 'Laporan';

    private ?string $periode = null;

    /** @var array<int, array{label: string, align: string}> */
    private array $kolom = [];

    /** @var array<int, array<int, string>> */
    private array $baris = [];

    /** @var array<int, array<int, string>> */
    private array $ringkasan = [];

    private string $orientation = 'portrait';

    private ?string $catatan = null;

    private bool $withTtd = true;

    public static function make(): self
    {
        return new self;
    }

    public function judul(string $judul): self
    {
        $this->judul = $judul;

        return $this;
    }

    public function periode(?string $periode): self
    {
        $this->periode = $periode;

        return $this;
    }

    /**
     * Definisikan kolom. Tiap elemen boleh berupa string (label, rata kiri)
     * atau array [label, align] dengan align: left|center|right.
     *
     * @param  array<int, string|array{0: string, 1?: string}>  $kolom
     */
    public function kolom(array $kolom): self
    {
        $this->kolom = array_map(function ($k): array {
            if (is_array($k)) {
                return ['label' => (string) $k[0], 'align' => $k[1] ?? 'left'];
            }

            return ['label' => (string) $k, 'align' => 'left'];
        }, $kolom);

        return $this;
    }

    /**
     * Baris data. Setiap baris adalah array sel (string/number/null) yang
     * urutannya sejajar dengan kolom. Nilai di-cast ke string apa adanya —
     * format angka/mata uang dilakukan oleh pemanggil.
     *
     * @param  array<int, array<int|string, string|int|float|null>>  $baris
     */
    public function baris(array $baris): self
    {
        $this->baris = array_map(
            fn ($row): array => array_map(fn ($c): string => (string) ($c ?? ''), array_values((array) $row)),
            $baris,
        );

        return $this;
    }

    /**
     * Baris ringkasan (tebal) di kaki tabel, mis. baris TOTAL.
     *
     * @param  array<int, array<int|string, string|int|float|null>>  $ringkasan
     */
    public function ringkasan(array $ringkasan): self
    {
        $this->ringkasan = array_map(
            fn ($row): array => array_map(fn ($c): string => (string) ($c ?? ''), array_values((array) $row)),
            $ringkasan,
        );

        return $this;
    }

    public function landscape(): self
    {
        $this->orientation = 'landscape';

        return $this;
    }

    public function catatan(?string $catatan): self
    {
        $this->catatan = $catatan;

        return $this;
    }

    public function tanpaTtd(): self
    {
        $this->withTtd = false;

        return $this;
    }

    public function render(): DomPDF
    {
        return Pdf::loadView('exports.laporan-keuangan', [
            'sekolah' => Sekolah::query()->first(),
            'judul' => $this->judul,
            'periode' => $this->periode,
            'kolom' => $this->kolom,
            'baris' => $this->baris,
            'ringkasan' => $this->ringkasan,
            'catatan' => $this->catatan,
            'withTtd' => $this->withTtd,
        ])->setPaper('a4', $this->orientation);
    }

    public function namaFile(string $slug): string
    {
        return Str::slug($slug).'.pdf';
    }
}
