<?php

namespace App\Filament\Resources\Absensis\Pages;

use App\Filament\Resources\Absensis\AbsensiResource;
use App\Models\Absensi;
use App\Models\JadwalPelajaran;
use App\Models\Siswa;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAbsensi extends CreateRecord
{
    protected static string $resource = AbsensiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $jadwal = JadwalPelajaran::query()
            ->whereKey($data['jadwal_pelajaran_id'] ?? null)
            ->where('is_active', true)
            ->first();

        if (! $jadwal) {
            throw ValidationException::withMessages([
                'jadwal_pelajaran_id' => 'Jadwal pelajaran tidak ditemukan atau tidak aktif.',
            ]);
        }

        $siswa = Siswa::query()
            ->whereKey($data['siswa_id'] ?? null)
            ->where('is_active', true)
            ->first();

        if (! $siswa) {
            throw ValidationException::withMessages([
                'siswa_id' => 'Siswa tidak ditemukan atau tidak aktif.',
            ]);
        }

        if ((int) $siswa->kelas_id !== (int) $jadwal->kelas_id) {
            throw ValidationException::withMessages([
                'siswa_id' => 'Siswa tidak terdaftar pada kelas jadwal yang dipilih.',
            ]);
        }

        $alreadyExists = Absensi::query()
            ->where('jadwal_pelajaran_id', $jadwal->id)
            ->where('siswa_id', $siswa->id)
            ->whereDate('tanggal', (string) ($data['tanggal'] ?? ''))
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'tanggal' => 'Absensi siswa pada jadwal dan tanggal ini sudah ada.',
            ]);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
