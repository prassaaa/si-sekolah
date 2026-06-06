<?php

namespace App\Models;

use Database\Factories\PembayaranFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pembayaran extends Model
{
    /** @use HasFactory<PembayaranFactory> */
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
            'applied_amount' => 'decimal:2',
            'applied_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    /**
     * @return BelongsTo<TagihanSiswa, Pembayaran>
     */
    public function tagihanSiswa(): BelongsTo
    {
        return $this->belongsTo(TagihanSiswa::class);
    }

    /**
     * @return BelongsTo<Pegawai, Pembayaran>
     */
    public function penerima(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'diterima_oleh');
    }

    /**
     * @return BelongsTo<UnitPos, Pembayaran>
     */
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

    protected const MONEY_SCALE = 2;

    protected static function booted(): void
    {
        static::created(function (Pembayaran $pembayaran) {
            static::reconcilePayment($pembayaran);
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

            static::reconcilePayment($pembayaran);
        });

        static::deleted(function (Pembayaran $pembayaran) {
            static::reversePayment($pembayaran);
        });

        static::restored(function (Pembayaran $pembayaran) {
            static::reconcilePayment($pembayaran);
        });

        static::forceDeleted(function (Pembayaran $pembayaran) {
            if (
                bccomp(
                    (string) ($pembayaran->applied_amount ?? '0'),
                    '0',
                    self::MONEY_SCALE,
                ) > 0
            ) {
                static::reversePayment($pembayaran);
            }
        });
    }

    /**
     * Reconcile this payment against its tagihan using applied_amount as the
     * source of truth. Reverses any previously applied amount (including from a
     * tagihan reassignment) and applies the current amount when the payment is
     * in a state that should be applied (status berhasil and not soft-deleted).
     */
    private static function reconcilePayment(Pembayaran $pembayaran): void
    {
        $oldTagihanId = (int) $pembayaran->getOriginal('tagihan_siswa_id');
        $newTagihanId = (int) $pembayaran->tagihan_siswa_id;
        $oldApplied = (string) ($pembayaran->applied_amount ?? '0');

        $shouldApply =
            $pembayaran->status === 'berhasil' &&
            $pembayaran->deleted_at === null;
        $newApplied = $shouldApply
            ? (string) ($pembayaran->jumlah_bayar ?? '0')
            : '0';

        DB::transaction(function () use (
            $pembayaran,
            $oldTagihanId,
            $newTagihanId,
            $oldApplied,
            $newApplied,
        ) {
            $tagihanIds = array_values(
                array_unique(
                    array_filter([$oldTagihanId, $newTagihanId]),
                ),
            );

            $tagihans = TagihanSiswa::query()
                ->whereIn('id', $tagihanIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if (
                $oldTagihanId &&
                bccomp($oldApplied, '0', self::MONEY_SCALE) > 0
            ) {
                $oldTagihan = $tagihans->get($oldTagihanId);
                if ($oldTagihan) {
                    static::adjustTagihanTerbayar(
                        $oldTagihan,
                        bcmul($oldApplied, '-1', self::MONEY_SCALE),
                    );
                }
            }

            if (
                $newTagihanId &&
                bccomp($newApplied, '0', self::MONEY_SCALE) > 0
            ) {
                $newTagihan = $tagihans->get($newTagihanId);
                if ($newTagihan) {
                    static::adjustTagihanTerbayar($newTagihan, $newApplied);
                }
            }

            $pembayaran->applied_amount = $newApplied;
            $pembayaran->applied_at =
                bccomp($newApplied, '0', self::MONEY_SCALE) > 0
                    ? now()
                    : null;
            $pembayaran->saveQuietly();

            foreach ($tagihans as $tagihan) {
                /** @phpstan-ignore-next-line */
                $tagihan->updateStatus();
            }
        });
    }

    /**
     * Reverse this payment's currently applied amount from its tagihan and
     * reset the applied tracking columns. Safe to call when nothing is applied.
     */
    private static function reversePayment(Pembayaran $pembayaran): void
    {
        $applied = (string) ($pembayaran->applied_amount ?? '0');
        $tagihanId = (int) $pembayaran->tagihan_siswa_id;

        if (bccomp($applied, '0', self::MONEY_SCALE) <= 0) {
            return;
        }

        DB::transaction(function () use ($pembayaran, $tagihanId, $applied) {
            if ($tagihanId) {
                $tagihan = TagihanSiswa::query()
                    ->lockForUpdate()
                    ->find($tagihanId);
                if ($tagihan) {
                    static::adjustTagihanTerbayar(
                        $tagihan,
                        bcmul($applied, '-1', self::MONEY_SCALE),
                    );
                    /** @phpstan-ignore-next-line */
                    $tagihan->updateStatus();
                }
            }

            $pembayaran->applied_amount = '0.00';
            $pembayaran->applied_at = null;
            $pembayaran->saveQuietly();
        });
    }

    private static function adjustTagihanTerbayar(
        TagihanSiswa $tagihan,
        string $delta,
    ): void {
        $tagihan->total_terbayar = bcadd(
            (string) $tagihan->total_terbayar,
            $delta,
            self::MONEY_SCALE,
        );
        $tagihan->save();
        $tagihan->refresh();
    }
}
