<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'SI Sekolah RFID API',
    description: 'API endpoint untuk hardware RFID reader (ESP32 + RC522) yang terintegrasi dengan sistem presensi harian SI Sekolah. Klik **Authorize** dulu untuk paste API token, lalu coba endpoint via **Try it out**.',
)]
#[OA\Server(url: 'http://43.133.156.101:8081', description: 'Production VPS')]
#[OA\Server(url: 'http://localhost:8000', description: 'Local development')]
#[OA\SecurityScheme(
    securityScheme: 'BearerAuth',
    type: 'http',
    scheme: 'bearer',
    description: 'Token API dari RfidDevice yang dibuat di Filament panel. Token plain hanya tampil sekali saat Create. Kalau hilang, gunakan action Regenerate Token di Filament.',
)]
abstract class Controller
{
    //
}
