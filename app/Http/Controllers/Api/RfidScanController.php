<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RfidScanRequest;
use App\Models\RfidDevice;
use App\Services\Rfid\PresensiScanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class RfidScanController extends Controller
{
    public function __construct(private readonly PresensiScanService $service) {}

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
