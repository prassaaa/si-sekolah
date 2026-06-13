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

        // Validasi cepat di sisi form (UX) menggunakan bcmath.
        // Validasi otoritatif dengan lock ada di reconcilePayment pada Pembayaran model.
        $jumlahBayar = bcadd((string) ($data['jumlah_bayar'] ?? '0'), '0', 2);
        $sisaTagihan = bcadd((string) $tagihan->sisa_tagihan, '0', 2);

        if (bccomp($jumlahBayar, $sisaTagihan, 2) > 0) {
            throw ValidationException::withMessages([
                'jumlah_bayar' => 'Jumlah bayar melebihi sisa tagihan.',
            ]);
        }

        return $data;
    }
}
