<?php

namespace App\Filament\Resources\PresensiHarians\Pages;

use App\Filament\Resources\PresensiHarians\PresensiHarianResource;
use App\Models\PresensiHarian;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreatePresensiHarian extends CreateRecord
{
    protected static string $resource = PresensiHarianResource::class;

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

        $duplicate = PresensiHarian::query()
            ->where('siswa_id', $data['siswa_id'] ?? null)
            ->whereDate('tanggal', $tanggal)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'data.tanggal' => 'Sudah ada data presensi untuk siswa ini pada tanggal tersebut.',
            ]);
        }

        $data['dicatat_oleh'] = Auth::id();
        $data['sumber_masuk'] = $data['sumber_masuk'] ?? 'manual';

        return $data;
    }
}
