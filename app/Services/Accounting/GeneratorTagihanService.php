<?php

namespace App\Services\Accounting;

use App\Models\JenisPembayaran;
use App\Models\PembayaranPaket;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\TagihanSiswa;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Membuat TagihanSiswa secara massal sebagai prasyarat operasional SPP bulanan
 * (temuan #24 / F8). Dua jalur:
 *
 *   - generateMassal: satu jenis pembayaran (utamanya bulanan) untuk seluruh
 *     siswa aktif pada satu periode (bulan + tahun).
 *   - terapkanPaket: seluruh jenis pembayaran dalam satu paket untuk satu siswa
 *     pada satu semester (nominal diambil dari pivot paket).
 *
 * Keduanya IDEMPOTEN: pembuatan dilewati bila tagihan untuk kombinasi kunci
 * sudah ada, sehingga aman dijalankan berulang tanpa menggandakan tagihan.
 * Pengecekan eksistensi memakai withTrashed agar tagihan yang sudah pernah
 * dibuat lalu di-soft-delete tidak diduplikasi. Seluruh proses dibungkus
 * DB::transaction.
 */
class GeneratorTagihanService
{
    /**
     * Generate tagihan satu jenis pembayaran untuk seluruh siswa aktif pada satu
     * periode (bulan + tahun). Idempoten per (siswa, jenis, periode_bulan,
     * periode_tahun): siswa yang sudah memiliki tagihan periode tersebut
     * dilewati.
     *
     * @return array{dibuat: int, dilewati: int}
     */
    public function generateMassal(
        JenisPembayaran $jenis,
        Semester $semester,
        ?int $kelasId,
        int $bulan,
        int $tahun,
    ): array {
        return DB::transaction(function () use ($jenis, $semester, $kelasId, $bulan, $tahun): array {
            $dibuat = 0;
            $dilewati = 0;

            $nominal = (string) $jenis->nominal;
            $jatuhTempo = $this->resolveJatuhTempo($jenis, $bulan, $tahun);

            $siswas = $this->siswaAktifQuery($kelasId)->get();

            foreach ($siswas as $siswa) {
                $sudahAda = TagihanSiswa::withTrashed()
                    ->where('siswa_id', $siswa->getKey())
                    ->where('jenis_pembayaran_id', $jenis->getKey())
                    ->where('periode_bulan', $bulan)
                    ->where('periode_tahun', $tahun)
                    ->lockForUpdate()
                    ->exists();

                if ($sudahAda) {
                    $dilewati++;

                    continue;
                }

                TagihanSiswa::create([
                    'siswa_id' => $siswa->getKey(),
                    'jenis_pembayaran_id' => $jenis->getKey(),
                    'semester_id' => $semester->getKey(),
                    'periode_bulan' => $bulan,
                    'periode_tahun' => $tahun,
                    'nomor_tagihan' => $this->nomorTagihanUnik(),
                    'nominal' => $nominal,
                    'diskon' => '0',
                    'total_tagihan' => $nominal,
                    'total_terbayar' => '0',
                    'sisa_tagihan' => $nominal,
                    'tanggal_tagihan' => now()->toDateString(),
                    'tanggal_jatuh_tempo' => $jatuhTempo,
                    'status' => 'belum_bayar',
                ]);

                $dibuat++;
            }

            return ['dibuat' => $dibuat, 'dilewati' => $dilewati];
        });
    }

    /**
     * Terapkan paket pembayaran ke satu siswa pada satu semester: buat satu
     * tagihan per jenis pembayaran dalam paket dengan nominal dari pivot.
     * Idempoten per (siswa, jenis, semester) — item paket yang tagihannya sudah
     * ada untuk semester tersebut dilewati.
     *
     * @return array{dibuat: int, dilewati: int}
     */
    public function terapkanPaket(
        PembayaranPaket $paket,
        Siswa $siswa,
        Semester $semester,
    ): array {
        return DB::transaction(function () use ($paket, $siswa, $semester): array {
            $dibuat = 0;
            $dilewati = 0;

            $items = $paket->jenisPembayarans()->get();

            foreach ($items as $jenis) {
                $sudahAda = TagihanSiswa::withTrashed()
                    ->where('siswa_id', $siswa->getKey())
                    ->where('jenis_pembayaran_id', $jenis->getKey())
                    ->where('semester_id', $semester->getKey())
                    ->lockForUpdate()
                    ->exists();

                if ($sudahAda) {
                    $dilewati++;

                    continue;
                }

                $nominal = (string) $jenis->pivot->nominal;

                TagihanSiswa::create([
                    'siswa_id' => $siswa->getKey(),
                    'jenis_pembayaran_id' => $jenis->getKey(),
                    'semester_id' => $semester->getKey(),
                    'periode_bulan' => null,
                    'periode_tahun' => null,
                    'nomor_tagihan' => $this->nomorTagihanUnik(),
                    'nominal' => $nominal,
                    'diskon' => '0',
                    'total_tagihan' => $nominal,
                    'total_terbayar' => '0',
                    'sisa_tagihan' => $nominal,
                    'tanggal_tagihan' => now()->toDateString(),
                    'tanggal_jatuh_tempo' => $this->resolvePaketJatuhTempo($jenis),
                    'status' => 'belum_bayar',
                ]);

                $dibuat++;
            }

            return ['dibuat' => $dibuat, 'dilewati' => $dilewati];
        });
    }

    /**
     * Query siswa aktif: status 'aktif' DAN is_active true, opsional discope
     * per kelas.
     *
     * @return Builder<Siswa>
     */
    private function siswaAktifQuery(?int $kelasId): Builder
    {
        $query = Siswa::query()
            ->where('status', 'aktif')
            ->where('is_active', true);

        if ($kelasId !== null) {
            $query->where('kelas_id', $kelasId);
        }

        return $query;
    }

    /**
     * Jatuh tempo tagihan bulanan: pakai tanggal jatuh tempo jenis pembayaran
     * bila ada, jika tidak gunakan akhir bulan periode.
     */
    private function resolveJatuhTempo(JenisPembayaran $jenis, int $bulan, int $tahun): string
    {
        if ($jenis->tanggal_jatuh_tempo !== null) {
            return Carbon::parse($jenis->tanggal_jatuh_tempo)->toDateString();
        }

        return Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();
    }

    /**
     * Jatuh tempo tagihan paket: pakai tanggal jatuh tempo jenis pembayaran bila
     * ada, jika tidak gunakan akhir bulan berjalan.
     */
    private function resolvePaketJatuhTempo(JenisPembayaran $jenis): string
    {
        if ($jenis->tanggal_jatuh_tempo !== null) {
            return Carbon::parse($jenis->tanggal_jatuh_tempo)->toDateString();
        }

        return now()->endOfMonth()->toDateString();
    }

    /**
     * Nomor tagihan unik. Diulang bila bertabrakan dengan nomor yang sudah ada
     * (termasuk yang soft-deleted) agar tidak melanggar unique index.
     */
    private function nomorTagihanUnik(): string
    {
        do {
            $nomor = 'TGH-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (
            TagihanSiswa::withTrashed()->where('nomor_tagihan', $nomor)->exists()
        );

        return $nomor;
    }
}
