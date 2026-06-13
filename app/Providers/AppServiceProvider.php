<?php

namespace App\Providers;

use App\Models\JurnalUmum;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Pembayaran;
use App\Models\TabunganSiswa;
use App\Services\Accounting\PeriodeGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Pemetaan model transaksi keuangan ke kolom tanggal yang menentukan
     * periode akuntansinya. Dipakai untuk mendaftarkan PeriodeGuard secara
     * terpusat tanpa menyentuh model transaksi.
     *
     * @var array<class-string<Model>, string>
     */
    private const PERIODE_GUARDED_MODELS = [
        JurnalUmum::class => 'tanggal',
        KasMasuk::class => 'tanggal',
        KasKeluar::class => 'tanggal',
        Pembayaran::class => 'tanggal_bayar',
        TabunganSiswa::class => 'tanggal',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPeriodeGuards();
    }

    /**
     * Daftarkan penjaga periode akuntansi pada setiap model transaksi.
     *
     * Pada event `saving` (create/update) dan `deleting`, tanggal record
     * diperiksa terhadap PeriodeAkuntansi. Bila periode tersebut tertutup,
     * ValidationException dilempar sehingga transaksi pada periode yang sudah
     * dikunci tidak dapat dibuat, diubah, ataupun dihapus.
     *
     * Posting jurnal otomatis bertanggal periode terbuka tetap lolos; reverse
     * (soft-delete) pada periode terbuka juga lolos. Hanya transaksi bertanggal
     * periode tertutup yang diblokir.
     */
    private function registerPeriodeGuards(): void
    {
        foreach (self::PERIODE_GUARDED_MODELS as $model => $kolomTanggal) {
            $assert = function (Model $record) use ($kolomTanggal): void {
                app(PeriodeGuard::class)->assertOpen($record->{$kolomTanggal});
            };

            $model::saving($assert);
            $model::deleting($assert);
        }
    }
}
