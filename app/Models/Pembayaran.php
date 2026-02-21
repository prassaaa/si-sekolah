<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pembayaran extends Model
{
    /** @use HasFactory<\Database\Factories\PembayaranFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tagihan_siswa_id',
        'nomor_transaksi',
        'tanggal_bayar',
        'jumlah_bayar',
        'metode_pembayaran',
        'referensi_pembayaran',
        'diterima_oleh',
        'unit_pos_id',
        'keterangan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_bayar' => 'date',
            'jumlah_bayar' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function tagihanSiswa(): BelongsTo
    {
        return $this->belongsTo(TagihanSiswa::class);
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'diterima_oleh');
    }

    public function unitPos(): BelongsTo
    {
        return $this->belongsTo(UnitPos::class);
    }

    public function getMetodeInfoAttribute(): string
    {
        return match ($this->metode_pembayaran) {
            'tunai' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'virtual_account' => 'Virtual Account',
            'lainnya' => 'Lainnya',
            default => $this->metode_pembayaran,
        };
    }

    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'pending' => ['label' => 'Pending', 'color' => 'warning'],
            'berhasil' => ['label' => 'Berhasil', 'color' => 'success'],
            'gagal' => ['label' => 'Gagal', 'color' => 'danger'],
            'batal' => ['label' => 'Batal', 'color' => 'gray'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }

    protected static function booted(): void
    {
        static::created(function (Pembayaran $pembayaran) {
            if ($pembayaran->status === 'berhasil') {
                static::applyPaymentToTagihan($pembayaran);
            }
        });

        static::updated(function (Pembayaran $pembayaran) {
            if (
                ! $pembayaran->wasChanged([
                    'status',
                    'jumlah_bayar',
                    'tagihan_siswa_id',
                ])
            ) {
                return;
            }

            static::reconcileUpdatedPayment($pembayaran);
        });

        static::deleted(function (Pembayaran $pembayaran) {
            if ($pembayaran->status === 'berhasil') {
                static::reversePaymentFromTagihan($pembayaran);
            }
        });

        static::restored(function (Pembayaran $pembayaran) {
            if ($pembayaran->status === 'berhasil') {
                static::applyPaymentToTagihan($pembayaran);
            }
        });
    }

    private static function applyPaymentToTagihan(Pembayaran $pembayaran): void
    {
        static::applyAmountToTagihan(
            (int) $pembayaran->tagihan_siswa_id,
            (float) $pembayaran->jumlah_bayar,
        );
    }

    private static function reversePaymentFromTagihan(
        Pembayaran $pembayaran,
    ): void {
        static::applyAmountToTagihan(
            (int) $pembayaran->tagihan_siswa_id,
            -1 * (float) $pembayaran->jumlah_bayar,
        );
    }

    private static function applyAmountToTagihan(
        int $tagihanId,
        float $amount,
    ): void {
        if (! $tagihanId || $amount === 0.0) {
            return;
        }

        DB::transaction(function () use ($tagihanId, $amount) {
            $tagihan = TagihanSiswa::query()->lockForUpdate()->find($tagihanId);
            if (! $tagihan) {
                return;
            }

            $tagihan->total_terbayar = (float) $tagihan->total_terbayar + $amount;
            $tagihan->sisa_tagihan = (float) $tagihan->sisa_tagihan - $amount;
            $tagihan->save();
            $tagihan->refresh();
            $tagihan->updateStatus();
        });
    }

    private static function reconcileUpdatedPayment(Pembayaran $pembayaran): void
    {
        $oldStatus = (string) $pembayaran->getOriginal('status');
        $newStatus = (string) $pembayaran->status;
        $oldTagihanId = (int) $pembayaran->getOriginal('tagihan_siswa_id');
        $newTagihanId = (int) $pembayaran->tagihan_siswa_id;
        $oldJumlah = (float) $pembayaran->getOriginal('jumlah_bayar');
        $newJumlah = (float) $pembayaran->jumlah_bayar;

        $oldApplied = $oldStatus === 'berhasil';
        $newApplied = $newStatus === 'berhasil';

        if (! $oldApplied && ! $newApplied) {
            return;
        }

        DB::transaction(function () use (
            $oldApplied,
            $newApplied,
            $oldTagihanId,
            $newTagihanId,
            $oldJumlah,
            $newJumlah
        ) {
            $tagihanIds = array_values(
                array_unique(array_filter([$oldTagihanId, $newTagihanId])),
            );

            $tagihans = TagihanSiswa::query()
                ->whereIn('id', $tagihanIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($oldApplied && $oldTagihanId) {
                $oldTagihan = $tagihans->get($oldTagihanId);
                if ($oldTagihan) {
                    $oldTagihan->total_terbayar =
                        (float) $oldTagihan->total_terbayar - $oldJumlah;
                    $oldTagihan->sisa_tagihan =
                        (float) $oldTagihan->sisa_tagihan + $oldJumlah;
                    $oldTagihan->save();
                }
            }

            if ($newApplied && $newTagihanId) {
                $newTagihan = $tagihans->get($newTagihanId);
                if ($newTagihan) {
                    $newTagihan->total_terbayar =
                        (float) $newTagihan->total_terbayar + $newJumlah;
                    $newTagihan->sisa_tagihan =
                        (float) $newTagihan->sisa_tagihan - $newJumlah;
                    $newTagihan->save();
                }
            }

            foreach ($tagihans as $tagihan) {
                $tagihan->refresh();
                $tagihan->updateStatus();
            }
        });
    }
}
