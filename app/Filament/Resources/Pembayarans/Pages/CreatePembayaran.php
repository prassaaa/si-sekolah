<?php

namespace App\Filament\Resources\Pembayarans\Pages;

use App\Filament\Resources\Pembayarans\PembayaranResource;
use App\Models\TagihanSiswa;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePembayaran extends CreateRecord
{
    protected static string $resource = PembayaranResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tagihan = TagihanSiswa::query()->find($data['tagihan_siswa_id'] ?? null);

        if (! $tagihan) {
            throw ValidationException::withMessages([
                'tagihan_siswa_id' => 'Tagihan tidak ditemukan.',
            ]);
        }

        $jumlahBayar = (float) ($data['jumlah_bayar'] ?? 0);
        $sisaTagihan = (float) $tagihan->sisa_tagihan;

        if ($jumlahBayar > $sisaTagihan) {
            throw ValidationException::withMessages([
                'jumlah_bayar' => 'Jumlah bayar melebihi sisa tagihan.',
            ]);
        }

        return $data;
    }
}
