<?php

namespace App\Services\Wa;

interface WaGatewayContract
{
    /**
     * Kirim pesan WhatsApp ke nomor tujuan.
     *
     * @return array{status: string, response: string}
     */
    public function kirim(string $nomor, string $pesan): array;
}
