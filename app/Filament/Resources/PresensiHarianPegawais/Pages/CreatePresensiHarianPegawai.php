<?php

namespace App\Filament\Resources\PresensiHarianPegawais\Pages;

use App\Filament\Resources\PresensiHarianPegawais\PresensiHarianPegawaiResource;
use App\Models\PresensiHarianPegawai;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreatePresensiHarianPegawai extends CreateRecord
{
    protected static string $resource = PresensiHarianPegawaiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tanggal = $data['tanggal'] ?? null;

        if (! $tanggal) {
            throw ValidationException::withMessages([
                'data.tanggal' => 'Tanggal wajib diisi.',
            ]);
        }

        $duplicate = PresensiHarianPegawai::query()
            ->where('pegawai_id', $data['pegawai_id'] ?? null)
            ->whereDate('tanggal', $tanggal)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'data.tanggal' => 'Sudah ada data presensi untuk pegawai ini pada tanggal tersebut.',
            ]);
        }

        $data['dicatat_oleh'] = Auth::id();
        $data['sumber_masuk'] = $data['sumber_masuk'] ?? 'manual';

        return $data;
    }
}
