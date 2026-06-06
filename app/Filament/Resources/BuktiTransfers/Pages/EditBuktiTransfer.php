<?php

namespace App\Filament\Resources\BuktiTransfers\Pages;

use App\Filament\Resources\BuktiTransfers\BuktiTransferResource;
use App\Models\Pembayaran;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditBuktiTransfer extends EditRecord
{
    protected static string $resource = BuktiTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        if ($record->status !== 'verified') {
            return;
        }

        if (! $record->tagihan_siswa_id) {
            return;
        }

        DB::transaction(function () use ($record) {
            $record->refresh();

            // Idempotency: already has a linked Pembayaran for this tagihan via transfer
            $alreadyLinked = Pembayaran::query()
                ->where('tagihan_siswa_id', $record->tagihan_siswa_id)
                ->where('referensi_pembayaran', 'BT-'.$record->id)
                ->exists();

            if ($alreadyLinked) {
                return;
            }

            // Stamp verified_by / verified_at if not already set
            if (! $record->verified_by || ! $record->verified_at) {
                $record->verified_by = auth()->id();
                $record->verified_at = now();
                $record->saveQuietly();
            }

            Pembayaran::create([
                'tagihan_siswa_id' => $record->tagihan_siswa_id,
                'nomor_transaksi' => 'TRF-'.strtoupper(uniqid()),
                'tanggal_bayar' => $record->tanggal_transfer ?? now()->toDateString(),
                'jumlah_bayar' => $record->nominal,
                'metode_pembayaran' => 'transfer',
                'referensi_pembayaran' => 'BT-'.$record->id,
                'diterima_oleh' => null,
                'keterangan' => 'Verifikasi bukti transfer #'.$record->id,
                'status' => 'berhasil',
            ]);
        });
    }
}
