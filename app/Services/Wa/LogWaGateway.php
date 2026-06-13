<?php

namespace App\Services\Wa;

use Illuminate\Support\Facades\Log;

/**
 * Driver WhatsApp default yang mencatat ke log aplikasi tanpa mengirim pesan nyata.
 * Berguna untuk pengembangan dan staging. Untuk produksi, ganti driver via WA_DRIVER.
 */
class LogWaGateway implements WaGatewayContract
{
    /**
     * Catat pesan ke log dan simulasikan pengiriman berhasil.
     *
     * @return array{status: string, response: string}
     */
    public function kirim(string $nomor, string $pesan): array
    {
        Log::info('[WA Log Driver] Pesan ke '.$nomor, [
            'nomor' => $nomor,
            'pesan' => $pesan,
        ]);

        return [
            'status' => 'terkirim',
            'response' => 'Log driver: pesan dicatat ke log, tidak dikirim ke nomor '.$nomor,
        ];
    }
}
