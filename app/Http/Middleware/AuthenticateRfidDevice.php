<?php

namespace App\Http\Middleware;

use App\Models\RfidDevice;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateRfidDevice
{
    /**
     * Authenticate hardware reader via Bearer token.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return $this->unauthorized('Missing bearer token');
        }

        $device = RfidDevice::query()
            ->where('is_active', true)
            ->get()
            ->first(fn (RfidDevice $candidate) => $candidate->verifyToken($token));

        if (! $device) {
            return $this->unauthorized('Invalid or inactive device token');
        }

        $request->attributes->set('rfid_device', $device);

        return $next($request);
    }

    private function unauthorized(string $reason): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized',
            'reason' => $reason,
        ], 401);
    }
}
