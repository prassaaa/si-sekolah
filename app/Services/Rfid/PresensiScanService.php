<?php

namespace App\Services\Rfid;

use App\Models\KartuRfid;
use App\Models\Pegawai;
use App\Models\PresensiHarian;
use App\Models\PresensiHarianPegawai;
use App\Models\RfidDevice;
use App\Models\RfidScanLog;
use App\Models\Sekolah;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PresensiScanService
{
    /**
     * @param  array<string, mixed>  $rawPayload
     * @return array<string, mixed>
     */
    public function handle(
        RfidDevice $device,
        string $uid,
        ?Carbon $scannedAt = null,
        array $rawPayload = []
    ): array {
        $scannedAt = $scannedAt ? CarbonImmutable::instance($scannedAt) : CarbonImmutable::now();
        $normalizedUid = $this->normalizeUid($uid);

        $kartu = KartuRfid::query()->with('owner')->where('uid', $normalizedUid)->first();

        if (! $kartu) {
            return $this->respondAndLog($device, $normalizedUid, null, null, 'tidak_dikenal',
                'Kartu tidak terdaftar', $scannedAt, $rawPayload, [
                    'success' => false,
                ]);
        }

        $owner = $kartu->owner;

        if ($kartu->status !== 'aktif') {
            return $this->respondAndLog($device, $normalizedUid, $kartu, $owner, 'ditolak',
                "Kartu berstatus {$kartu->status}, hubungi TU", $scannedAt, $rawPayload, [
                    'success' => false,
                    'pemilik' => ['nama' => $owner?->nama],
                ]);
        }

        $sekolah = Sekolah::query()->first();

        if ($sekolah && $this->isDuplicateScan($normalizedUid, $scannedAt, (int) $sekolah->debounce_scan_detik)) {
            return $this->respondAndLog($device, $normalizedUid, $kartu, $owner, 'duplikat',
                'Tap terlalu cepat (debounce window)', $scannedAt, $rawPayload, [
                    'success' => false,
                ]);
        }

        return DB::transaction(function () use ($device, $kartu, $owner, $sekolah, $scannedAt, $normalizedUid, $rawPayload) {
            $presensiClass = $owner instanceof Pegawai ? PresensiHarianPegawai::class : PresensiHarian::class;
            $foreignKey = $owner instanceof Pegawai ? 'pegawai_id' : 'siswa_id';

            $existing = $presensiClass::query()
                ->where($foreignKey, $owner->id)
                ->whereDate('tanggal', $scannedAt->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $existing) {
                return $this->createMasukRecord($device, $kartu, $owner, $sekolah, $scannedAt, $normalizedUid, $rawPayload);
            }

            if ($existing->jam_pulang !== null) {
                return $this->respondAndLog($device, $normalizedUid, $kartu, $owner, 'duplikat',
                    'Sudah tap masuk dan pulang hari ini', $scannedAt, $rawPayload, [
                        'success' => false,
                    ]);
            }

            return $this->updatePulangRecord($device, $kartu, $owner, $sekolah, $existing, $scannedAt, $normalizedUid, $rawPayload);
        });
    }

    /**
     * @param  array<string, mixed>  $rawPayload
     * @return array<string, mixed>
     */
    private function createMasukRecord(
        RfidDevice $device,
        KartuRfid $kartu,
        Model $owner,
        ?Sekolah $sekolah,
        CarbonImmutable $scannedAt,
        string $uid,
        array $rawPayload
    ): array {
        [$status, $terlambatMenit] = $this->calculateMasukStatus($scannedAt, $sekolah);

        $isPegawai = $owner instanceof Pegawai;
        $presensiClass = $isPegawai ? PresensiHarianPegawai::class : PresensiHarian::class;
        $foreignKey = $isPegawai ? 'pegawai_id' : 'siswa_id';

        $presensi = $presensiClass::create([
            $foreignKey => $owner->id,
            'tanggal' => $scannedAt->toDateString(),
            'jam_masuk' => $scannedAt->format('H:i:s'),
            'status' => $status,
            'sumber_masuk' => 'rfid',
            'terlambat_menit' => $terlambatMenit,
        ]);

        $pesan = $status === 'terlambat'
            ? "Selamat datang {$owner->nama}, Anda terlambat {$terlambatMenit} menit"
            : "Selamat datang {$owner->nama}";

        $pemilikInfo = $isPegawai
            ? ['nama' => $owner->nama, 'jabatan' => $owner->jabatan?->nama, 'tipe' => 'pegawai']
            : ['nama' => $owner->nama, 'kelas' => $owner->kelas?->nama, 'tipe' => 'siswa'];

        return $this->respondAndLog($device, $uid, $kartu, $owner, 'masuk', $pesan, $scannedAt, $rawPayload, [
            'success' => true,
            'pemilik' => $pemilikInfo,
            'presensi' => [
                'status' => $status,
                'jam_masuk' => $scannedAt->format('H:i:s'),
                'terlambat_menit' => $terlambatMenit,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $rawPayload
     * @return array<string, mixed>
     */
    private function updatePulangRecord(
        RfidDevice $device,
        KartuRfid $kartu,
        Model $owner,
        ?Sekolah $sekolah,
        Model $presensi,
        CarbonImmutable $scannedAt,
        string $uid,
        array $rawPayload
    ): array {
        $jamPulangMinimal = $sekolah?->jam_pulang_minimal ? Carbon::parse($sekolah->jam_pulang_minimal)->format('H:i:s') : '12:00:00';

        if ($scannedAt->format('H:i:s') < $jamPulangMinimal) {
            return $this->respondAndLog($device, $uid, $kartu, $owner, 'ditolak',
                "Belum waktunya pulang (minimum jam {$jamPulangMinimal})", $scannedAt, $rawPayload, [
                    'success' => false,
                ]);
        }

        $presensi->update([
            'jam_pulang' => $scannedAt->format('H:i:s'),
            'sumber_pulang' => 'rfid',
        ]);

        $isPegawai = $owner instanceof Pegawai;
        $pemilikInfo = $isPegawai
            ? ['nama' => $owner->nama, 'jabatan' => $owner->jabatan?->nama, 'tipe' => 'pegawai']
            : ['nama' => $owner->nama, 'kelas' => $owner->kelas?->nama, 'tipe' => 'siswa'];

        return $this->respondAndLog($device, $uid, $kartu, $owner, 'pulang',
            "Selamat jalan {$owner->nama}", $scannedAt, $rawPayload, [
                'success' => true,
                'pemilik' => $pemilikInfo,
                'presensi' => [
                    'status' => $presensi->status,
                    'jam_masuk' => $presensi->jam_masuk?->format('H:i:s'),
                    'jam_pulang' => $presensi->jam_pulang?->format('H:i:s'),
                ],
            ]);
    }

    private function isDuplicateScan(string $uid, CarbonImmutable $scannedAt, int $debounceSeconds): bool
    {
        if ($debounceSeconds <= 0) {
            return false;
        }

        return RfidScanLog::query()
            ->where('uid', $uid)
            ->whereIn('jenis', ['masuk', 'pulang'])
            ->where('scanned_at', '>=', $scannedAt->subSeconds($debounceSeconds))
            ->where('scanned_at', '<', $scannedAt)
            ->exists();
    }

    /**
     * @return array{0: string, 1: int|null}
     */
    private function calculateMasukStatus(CarbonImmutable $scannedAt, ?Sekolah $sekolah): array
    {
        if (! $sekolah) {
            return ['hadir', null];
        }

        $jamMasukDefault = Carbon::parse($sekolah->jam_masuk_default);
        $batasTerlambatMenit = (int) $sekolah->batas_terlambat_menit;
        $batas = $jamMasukDefault->copy()->addMinutes($batasTerlambatMenit);

        $scanTime = Carbon::parse($scannedAt->format('H:i:s'));

        if ($scanTime->greaterThan($batas)) {
            $terlambatMenit = (int) $jamMasukDefault->diffInMinutes($scanTime);

            return ['terlambat', $terlambatMenit];
        }

        return ['hadir', null];
    }

    private function normalizeUid(string $value): string
    {
        return strtoupper((string) preg_replace('/[^0-9A-Fa-f]/', '', $value));
    }

    /**
     * @param  array<string, mixed>  $rawPayload
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function respondAndLog(
        RfidDevice $device,
        string $uid,
        ?KartuRfid $kartu,
        ?Model $owner,
        string $jenis,
        string $pesan,
        CarbonImmutable $scannedAt,
        array $rawPayload,
        array $response
    ): array {
        $payload = array_merge(['jenis' => $jenis, 'pesan' => $pesan], $response);

        RfidScanLog::create([
            'uid' => $uid,
            'kartu_rfid_id' => $kartu?->id,
            'owner_type' => $owner ? $owner::class : null,
            'owner_id' => $owner?->id,
            'rfid_device_id' => $device->id,
            'jenis' => $jenis,
            'pesan' => $pesan,
            'request_payload' => $rawPayload,
            'response_payload' => $payload,
            'scanned_at' => $scannedAt,
        ]);

        $device->tandaiAktif();

        return $payload;
    }
}
