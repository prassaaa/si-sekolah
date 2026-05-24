<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RfidScanRequest;
use App\Models\RfidDevice;
use App\Services\Rfid\PresensiScanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class RfidScanController extends Controller
{
    public function __construct(private readonly PresensiScanService $service) {}

    #[OA\Post(
        path: '/api/rfid/scan',
        summary: 'Submit hasil tap kartu RFID',
        description: 'Endpoint utama untuk hardware reader (ESP32 + RC522). Server otomatis route ke presensi siswa atau pegawai berdasarkan owner kartu. Selalu tulis ke rfid_scan_logs untuk audit.',
        security: [['BearerAuth' => []]],
        tags: ['RFID Scan'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['uid'],
                properties: [
                    new OA\Property(property: 'uid', type: 'string', maxLength: 32, example: '04A1B2C3', description: 'UID kartu (hex, server normalize uppercase tanpa separator)'),
                    new OA\Property(property: 'scanned_at', type: 'string', format: 'date-time', example: '2026-05-24T07:05:32+07:00', description: 'Optional, default now() server'),
                    new OA\Property(property: 'device_kode', type: 'string', maxLength: 50, example: 'GERBANG-IN-01', description: 'Optional, double-check device'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Request diproses (lihat success + jenis untuk detail)',
                content: new OA\JsonContent(
                    examples: [
                        'masuk' => new OA\Examples(
                            example: 'masuk',
                            summary: 'Tap masuk berhasil (hadir)',
                            value: [
                                'success' => true,
                                'jenis' => 'masuk',
                                'pesan' => 'Selamat datang Ahmad Setiawan',
                                'pemilik' => ['nama' => 'Ahmad Setiawan', 'kelas' => 'VII-A', 'tipe' => 'siswa'],
                                'presensi' => ['status' => 'hadir', 'jam_masuk' => '06:55:00', 'terlambat_menit' => null],
                            ],
                        ),
                        'terlambat' => new OA\Examples(
                            example: 'terlambat',
                            summary: 'Tap masuk berhasil (terlambat)',
                            value: [
                                'success' => true,
                                'jenis' => 'masuk',
                                'pesan' => 'Selamat datang Ahmad, Anda terlambat 25 menit',
                                'pemilik' => ['nama' => 'Ahmad Setiawan', 'kelas' => 'VII-A', 'tipe' => 'siswa'],
                                'presensi' => ['status' => 'terlambat', 'jam_masuk' => '07:25:00', 'terlambat_menit' => 25],
                            ],
                        ),
                        'pulang' => new OA\Examples(
                            example: 'pulang',
                            summary: 'Tap pulang berhasil',
                            value: [
                                'success' => true,
                                'jenis' => 'pulang',
                                'pesan' => 'Selamat jalan Ahmad Setiawan',
                                'pemilik' => ['nama' => 'Ahmad Setiawan', 'tipe' => 'siswa'],
                                'presensi' => ['status' => 'hadir', 'jam_masuk' => '06:55:00', 'jam_pulang' => '13:05:00'],
                            ],
                        ),
                        'tidak_dikenal' => new OA\Examples(
                            example: 'tidak_dikenal',
                            summary: 'UID tidak terdaftar',
                            value: ['success' => false, 'jenis' => 'tidak_dikenal', 'pesan' => 'Kartu tidak terdaftar'],
                        ),
                        'ditolak' => new OA\Examples(
                            example: 'ditolak',
                            summary: 'Kartu nonaktif/hilang',
                            value: ['success' => false, 'jenis' => 'ditolak', 'pesan' => 'Kartu berstatus hilang, hubungi TU'],
                        ),
                        'duplikat' => new OA\Examples(
                            example: 'duplikat',
                            summary: 'Tap dalam debounce window (60s default)',
                            value: ['success' => false, 'jenis' => 'duplikat', 'pesan' => 'Tap terlalu cepat (debounce window)'],
                        ),
                    ],
                ),
            ),
            new OA\Response(
                response: 401,
                description: 'Token tidak ada / salah / device nonaktif',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthorized'),
                        new OA\Property(property: 'reason', type: 'string', example: 'Missing bearer token'),
                    ],
                ),
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error (uid kosong / format salah)',
            ),
        ],
    )]
    public function store(RfidScanRequest $request): JsonResponse
    {
        /** @var RfidDevice $device */
        $device = $request->attributes->get('rfid_device');

        $scannedAt = $request->input('scanned_at')
            ? Carbon::parse($request->input('scanned_at'))
            : Carbon::now();

        $result = $this->service->handle(
            device: $device,
            uid: $request->input('uid'),
            scannedAt: $scannedAt,
            rawPayload: $request->all(),
        );

        return response()->json($result);
    }
}
