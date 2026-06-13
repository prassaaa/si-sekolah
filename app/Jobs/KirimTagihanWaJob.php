<?php

namespace App\Jobs;

use App\Models\NotifikasiTagihan;
use App\Services\Wa\WaGatewayContract;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class KirimTagihanWaJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * Buat instance job baru.
     */
    public function __construct(public readonly NotifikasiTagihan $notifikasi) {}

    /**
     * Jalankan job: kirim WA via driver aktif lalu perbarui status notifikasi.
     */
    public function handle(): void
    {
        $gateway = $this->resolveGateway();

        try {
            $result = $gateway->kirim(
                $this->notifikasi->tujuan_nomor,
                $this->notifikasi->pesan,
            );

            $this->notifikasi->update([
                'status' => $result['status'] === 'terkirim' ? 'terkirim' : 'gagal',
                'response' => $result['response'],
                'sent_at' => Carbon::now(),
            ]);
        } catch (\Throwable $e) {
            $this->notifikasi->update([
                'status' => 'gagal',
                'response' => $e->getMessage(),
                'sent_at' => Carbon::now(),
            ]);

            throw $e;
        }
    }

    /**
     * Resolve gateway driver dari konfigurasi tanpa menyentuh AppServiceProvider.
     */
    private function resolveGateway(): WaGatewayContract
    {
        $driverNama = $this->notifikasi->driver ?: config('wa.driver', 'log');
        $driverClass = config('wa.drivers.'.$driverNama);

        if (! $driverClass || ! class_exists($driverClass)) {
            $driverClass = config('wa.drivers.log');
        }

        return app($driverClass);
    }
}
