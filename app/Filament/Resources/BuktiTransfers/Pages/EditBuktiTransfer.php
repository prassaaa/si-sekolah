<?php

namespace App\Filament\Resources\BuktiTransfers\Pages;

use App\Filament\Resources\BuktiTransfers\BuktiTransferResource;
use App\Models\Pembayaran;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Throwable;

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

            // Cari Pembayaran yang sudah ada berdasarkan referensi saja (tanpa
            // filter tagihan_siswa_id) agar perubahan tagihan pada bukti yang
            // sudah verified tidak membuat Pembayaran ganda.
            // withTrashed() digunakan agar Pembayaran yang pernah di-soft-delete
            // dapat dipulihkan/diperbarui alih-alih membuat entri baru yang
            // berisiko dobel-akui ketika di-restore.
            $pembayaran = Pembayaran::withTrashed()
                ->where('referensi_pembayaran', 'BT-'.$record->id)
                ->first();

            if ($pembayaran !== null) {
                // Pembayaran sudah ada; pulihkan bila soft-deleted lalu pindahkan
                // ke tagihan baru bila berbeda. Event updated akan memicu
                // reconcilePayment yang merekonsiliasi tagihan lama dan baru.
                if ($pembayaran->trashed()) {
                    $pembayaran->restore();
                    $pembayaran->refresh();
                }

                if ((int) $pembayaran->tagihan_siswa_id !== (int) $record->tagihan_siswa_id) {
                    $pembayaran->update(['tagihan_siswa_id' => $record->tagihan_siswa_id]);
                }

                return;
            }

            // Stamp verified_by / verified_at jika belum diset
            if (! $record->verified_by || ! $record->verified_at) {
                $record->verified_by = auth()->id();
                $record->verified_at = now();
                $record->saveQuietly();
            }

            // BuktiTransfer memverifikasi transfer nyata yang sudah terjadi —
            // validasi overpayment dilewati (temuan #45, belum diselesaikan).
            // Flag direset di blok finally agar tidak bocor ke request lain.
            Pembayaran::$skipOverpayValidation = true;

            try {
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
            } catch (Throwable $e) {
                Pembayaran::$skipOverpayValidation = false;
                throw $e;
            }

            Pembayaran::$skipOverpayValidation = false;
        });
    }
}
