<?php

namespace App\Filament\Resources\Pembayarans\Pages;

use App\Filament\Resources\Pembayarans\PembayaranResource;
use App\Models\TagihanSiswa;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditPembayaran extends EditRecord
{
    protected static string $resource = PembayaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $tagihan = TagihanSiswa::query()->find($data['tagihan_siswa_id'] ?? null);

        if (! $tagihan) {
            throw ValidationException::withMessages([
                'tagihan_siswa_id' => 'Tagihan tidak ditemukan.',
            ]);
        }

        $jumlahBayar = (float) ($data['jumlah_bayar'] ?? 0);
        $sisaTagihan = (float) $tagihan->sisa_tagihan;

        if (
            $this->record->status === 'berhasil' &&
            (int) $this->record->tagihan_siswa_id === (int) $tagihan->id
        ) {
            $sisaTagihan += (float) $this->record->jumlah_bayar;
        }

        if ($jumlahBayar > $sisaTagihan) {
            throw ValidationException::withMessages([
                'jumlah_bayar' => 'Jumlah bayar melebihi sisa tagihan.',
            ]);
        }

        return $data;
    }
}
