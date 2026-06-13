<?php

namespace Database\Seeders;

use App\Models\JenisPembayaran;
use App\Models\Pegawai;
use App\Models\Pembayaran;
use App\Models\Semester;
use App\Models\SettingGaji;
use App\Models\Siswa;
use App\Models\SlipGaji;
use App\Models\TabunganSiswa;
use App\Models\TagihanSiswa;
use App\Models\UnitPos;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Membuat transaksi keuangan demo PASCA cut-off yang JUJUR: berjalan dengan
 * model event AKTIF sehingga jurnal benar-benar dibentuk oleh poster/observer
 * produksi — bukan jurnal palsu (temuan audit #3).
 *
 * Yang dihasilkan (semua bertanggal >= config('akuntansi.cutoff_posting')):
 *  - Pembayaran SPP berhasil → PembayaranObserver → D Kas / K Pendapatan.
 *  - Slip gaji draft yang di-approve() lalu bayar() → akrual D Beban Gaji /
 *    K Hutang Gaji, lalu KasKeluar otomatis dijurnal D Hutang Gaji / K Kas.
 *  - Setoran & penarikan tabungan → TabunganSiswaObserver → D/K Titipan.
 *
 * Idempoten: setiap entitas dicek keberadaannya via penanda unik sebelum dibuat
 * sehingga menjalankan ulang seeder tidak menggandakan data/jurnal. Bila
 * prasyarat (siswa/jenis pembayaran/pegawai/akun) belum ada, bagian terkait
 * dilewati dengan peringatan — tidak pernah melempar error.
 */
class DemoKeuanganPascaCutoffSeeder extends Seeder
{
    public function run(): void
    {
        $tanggalDasar = $this->tanggalDasar();

        $this->seedPembayaran($tanggalDasar);
        $this->seedSlipGaji($tanggalDasar);
        $this->seedTabungan($tanggalDasar);
    }

    /**
     * Tanggal dasar demo: awal bulan cut-off (selalu >= cut-off). Memakai bulan
     * cut-off membuat data demo stabil tak peduli kapan seeder dijalankan.
     */
    private function tanggalDasar(): Carbon
    {
        return Carbon::parse(config('akuntansi.cutoff_posting'))->startOfMonth();
    }

    /**
     * Beberapa pembayaran SPP berhasil pasca cut-off pada tagihan baru
     * (total_terbayar = 0) agar reconcile + poster berjalan wajar.
     */
    private function seedPembayaran(Carbon $tanggalDasar): void
    {
        $semester = Semester::query()->where('is_active', true)->first()
            ?? Semester::query()->first();
        $jenis = JenisPembayaran::query()->where('is_active', true)->first();
        $siswas = Siswa::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->take(5)
            ->get();
        $unitPos = UnitPos::query()->where('is_active', true)->first();
        $pegawai = Pegawai::query()->where('is_active', true)->first();

        if ($semester === null || $jenis === null || $siswas->isEmpty()) {
            $this->command->warn('DemoKeuanganPascaCutoff: prasyarat pembayaran tidak lengkap, dilewati.');

            return;
        }

        $nominal = (int) ($jenis->nominal > 0 ? $jenis->nominal : 300000);
        $dibuat = 0;

        foreach ($siswas as $index => $siswa) {
            $nomorTagihan = 'TGH-DEMO-'.$tanggalDasar->format('Ym').'-'.str_pad((string) $siswa->id, 4, '0', STR_PAD_LEFT);

            if (TagihanSiswa::query()->where('nomor_tagihan', $nomorTagihan)->exists()) {
                continue;
            }

            $tanggal = $tanggalDasar->copy()->addDays($index + 1);

            $tagihan = TagihanSiswa::create([
                'siswa_id' => $siswa->id,
                'jenis_pembayaran_id' => $jenis->id,
                'semester_id' => $semester->id,
                'nomor_tagihan' => $nomorTagihan,
                'nominal' => $nominal,
                'diskon' => 0,
                'total_tagihan' => $nominal,
                'total_terbayar' => 0,
                'sisa_tagihan' => $nominal,
                'tanggal_tagihan' => $tanggal->toDateString(),
                'tanggal_jatuh_tempo' => $tanggal->copy()->addDays(10)->toDateString(),
                'status' => 'belum_bayar',
                'keterangan' => 'Tagihan SPP demo pasca cut-off',
            ]);

            Pembayaran::create([
                'tagihan_siswa_id' => $tagihan->id,
                'nomor_transaksi' => 'PAY-DEMO-'.$tanggalDasar->format('Ymd').'-'.str_pad((string) $siswa->id, 4, '0', STR_PAD_LEFT),
                'tanggal_bayar' => $tanggal->toDateString(),
                'jumlah_bayar' => $nominal,
                'metode_pembayaran' => 'tunai',
                'diterima_oleh' => $pegawai?->id,
                'unit_pos_id' => $unitPos?->id,
                'status' => 'berhasil',
                'keterangan' => 'Pembayaran SPP demo pasca cut-off',
            ]);

            $dibuat++;
        }

        $this->command->info("DemoKeuanganPascaCutoff: {$dibuat} pembayaran SPP terjurnal.");
    }

    /**
     * Satu-dua slip gaji draft pasca cut-off, lalu approve() + bayar() agar
     * akrual gaji & pembayaran (KasKeluar) benar-benar dijurnal.
     */
    private function seedSlipGaji(Carbon $tanggalDasar): void
    {
        $settings = SettingGaji::query()
            ->with('pegawai')
            ->where('is_active', true)
            ->take(2)
            ->get();

        if ($settings->isEmpty()) {
            $this->command->warn('DemoKeuanganPascaCutoff: tidak ada setting gaji aktif, slip gaji dilewati.');

            return;
        }

        $tahun = (int) $tanggalDasar->year;
        $bulan = (int) $tanggalDasar->month;
        $dibuat = 0;

        // Simpan test-now sebelumnya (bila ada) untuk dipulihkan; seeder bisa
        // dijalankan dari test yang sudah membekukan waktu.
        $testNowSebelumnya = Carbon::getTestNow();

        foreach ($settings as $setting) {
            $exists = SlipGaji::query()
                ->where('pegawai_id', $setting->pegawai_id)
                ->where('tahun', $tahun)
                ->where('bulan', $bulan)
                ->exists();

            if ($exists) {
                continue;
            }

            $totalTunjangan = (string) (
                $setting->tunjangan_jabatan
                + $setting->tunjangan_kehadiran
                + $setting->tunjangan_transport
                + $setting->tunjangan_makan
                + $setting->tunjangan_lainnya
            );
            $totalPotongan = (string) (
                $setting->potongan_bpjs
                + $setting->potongan_pph21
                + $setting->potongan_lainnya
            );
            $gajiBersih = bcsub(bcadd((string) $setting->gaji_pokok, $totalTunjangan, 2), $totalPotongan, 2);

            $slip = DB::transaction(fn (): SlipGaji => SlipGaji::create([
                'pegawai_id' => $setting->pegawai_id,
                'setting_gaji_id' => $setting->id,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'gaji_pokok' => $setting->gaji_pokok,
                'total_tunjangan' => $totalTunjangan,
                'total_potongan' => $totalPotongan,
                'gaji_bersih' => $gajiBersih,
                'status' => 'draft',
                'catatan' => 'Slip gaji demo pasca cut-off',
            ]));

            // approve()/bayar() menandai tanggal akrual & KasKeluar dengan now().
            // Demo dijalankan sebelum cut-off (TA berjalan), jadi sementara
            // setel "now" ke tanggal dasar (>= cut-off) agar akrual & pembayaran
            // benar-benar terjurnal lewat poster produksi. Direset setelahnya.
            Carbon::setTestNow($tanggalDasar->copy()->addDay());

            try {
                $slip->approve();
                $slip->refresh();
                $slip->bayar();
            } finally {
                Carbon::setTestNow($testNowSebelumnya);
            }

            $dibuat++;
        }

        $this->command->info("DemoKeuanganPascaCutoff: {$dibuat} slip gaji di-approve & dibayar (terjurnal).");
    }

    /**
     * Setoran lalu penarikan tabungan pasca cut-off untuk beberapa siswa agar
     * jurnal titipan tabungan (D/K Kas/Titipan) terbentuk nyata.
     */
    private function seedTabungan(Carbon $tanggalDasar): void
    {
        $penanda = 'Tabungan demo pasca cut-off';

        // Idempoten: bila demo tabungan sudah pernah dibuat, jangan ulangi
        // (mencegah memilih siswa baru pada eksekusi berikutnya).
        if (TabunganSiswa::query()->where('keterangan', $penanda)->exists()) {
            return;
        }

        // Pakai siswa yang BELUM punya tabungan apa pun agar setor/tarik demo
        // self-contained (setor 100.000 lalu tarik 40.000 selalu tercover) dan
        // tidak terpengaruh saldo legacy pra-pembukuan.
        $siswaPunyaTabungan = TabunganSiswa::query()
            ->distinct()
            ->pluck('siswa_id')
            ->all();

        $siswas = Siswa::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereNotIn('id', $siswaPunyaTabungan)
            ->take(3)
            ->get();

        if ($siswas->isEmpty()) {
            $this->command->warn('DemoKeuanganPascaCutoff: tidak ada siswa tanpa tabungan, tabungan dilewati.');

            return;
        }

        $dibuat = 0;

        foreach ($siswas as $siswa) {
            // Setor 100.000 lalu tarik 40.000; observer memposting tiap baris.
            TabunganSiswa::create([
                'siswa_id' => $siswa->id,
                'jenis' => 'setor',
                'nominal' => 100000,
                'tanggal' => $tanggalDasar->copy()->addDays(2)->toDateString(),
                'keterangan' => $penanda,
            ]);

            TabunganSiswa::create([
                'siswa_id' => $siswa->id,
                'jenis' => 'tarik',
                'nominal' => 40000,
                'tanggal' => $tanggalDasar->copy()->addDays(5)->toDateString(),
                'keterangan' => $penanda,
            ]);

            $dibuat++;
        }

        $this->command->info("DemoKeuanganPascaCutoff: {$dibuat} siswa dengan tabungan terjurnal.");
    }
}
