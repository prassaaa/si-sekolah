<?php

namespace App\Filament\Resources\Absensis\Pages;

use App\Filament\Resources\Absensis\AbsensiResource;
use App\Models\Absensi;
use App\Models\JadwalPelajaran;
use App\Models\Siswa;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAbsensi extends EditRecord
{
    protected static string $resource = AbsensiResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['kelas_id'] = $this->record->jadwalPelajaran?->kelas_id;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $jadwal = JadwalPelajaran::query()
            ->whereKey($data['jadwal_pelajaran_id'] ?? null)
            ->first();

        if (! $jadwal) {
            throw ValidationException::withMessages([
                'jadwal_pelajaran_id' => 'Jadwal pelajaran tidak ditemukan.',
            ]);
        }

        if (
            (! $jadwal->is_active) &&
            ((int) $jadwal->id !== (int) $this->record->jadwal_pelajaran_id)
        ) {
            throw ValidationException::withMessages([
                'jadwal_pelajaran_id' => 'Jadwal pelajaran tidak aktif.',
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
            ->where('id', '!=', $this->record->getKey())
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'tanggal' => 'Absensi siswa pada jadwal dan tanggal ini sudah ada.',
            ]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
