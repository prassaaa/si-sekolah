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

        $jumlahBayar = (string) ($data['jumlah_bayar'] ?? '0');
        $available = (string) $tagihan->sisa_tagihan;

        if ((int) $this->record->tagihan_siswa_id === (int) $tagihan->id) {
            $available = bcadd(
                $available,
                (string) ($this->record->applied_amount ?? '0'),
                2,
            );
        }

        $willApply =
            ($data['status'] ?? null) === 'berhasil'
                ? $jumlahBayar
                : '0';

        if (bccomp($willApply, $available, 2) > 0) {
            throw ValidationException::withMessages([
                'jumlah_bayar' => 'Jumlah bayar melebihi sisa tagihan.',
            ]);
        }

        return $data;
    }
}
