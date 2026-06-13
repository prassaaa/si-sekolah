<?php

namespace App\Models;

use Database\Factories\PembayaranFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

    /**
     * Bypass flag untuk jalur BuktiTransfer (BT).
     *
     * BuktiTransfer memverifikasi transfer bank nyata yang sudah terjadi —
     * nominal BT tidak selalu sama dengan sisa tagihan (misalnya: pembayaran
     * lebih dari yang seharusnya). Validasi overpayment untuk jalur BT adalah
     * temuan terpisah (#45) yang belum diselesaikan dan akan menutup flag ini.
     *
     * PENGGUNAAN: set `true` sesaat sebelum Pembayaran::create() di
     * EditBuktiTransfer::afterSave(), reset ke `false` setelah create.
     * Jangan gunakan di jalur kasir biasa (CreatePembayaran / EditPembayaran).
     *
     * @internal Akan dihapus saat temuan #45 diselesaikan.
     */
    public static bool $skipOverpayValidation = false;

    protected static function booted(): void
    {
        static::creating(function (Pembayaran $pembayaran) {
            // Validasi sebelum INSERT: mencegah row terbentuk bila overpayment.
            // reconcilePayment (called on created) juga memvalidasi ulang setelah
            // lock diperoleh sehingga race condition antara dua request paralel
            // yang sama-sama lolos pre-insert juga tertangkap di sana.
            static::assertPaymentWithinTagihanLimit($pembayaran);
        });

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
     * Validasi sebelum INSERT: hanya berlaku untuk pembayaran dengan status
     * berhasil. Mengunci baris tagihan (lockForUpdate) di dalam transaksi
     * sehingga lock bertahan sampai INSERT selesai bila dipanggil dari dalam
     * DB::transaction luar (misalnya dari handleRecordCreation di halaman Filament).
     *
     * Bila dipanggil tanpa transaksi luar (model::create() langsung), lock
     * dilepas setelah validasi namun ValidationException masih mencegah INSERT
     * karena hook creating dijalankan sebelum Eloquent menulis baris.
     *
     * @throws ValidationException
     */
    private static function assertPaymentWithinTagihanLimit(Pembayaran $pembayaran): void
    {
        if (static::$skipOverpayValidation) {
            return;
        }

        $shouldApply =
            $pembayaran->status === 'berhasil' &&
            $pembayaran->deleted_at === null;

        if (! $shouldApply) {
            return;
        }

        $tagihanId = (int) ($pembayaran->tagihan_siswa_id ?? 0);

        if (! $tagihanId) {
            return;
        }

        $newApplied = bcadd((string) ($pembayaran->jumlah_bayar ?? '0'), '0', self::MONEY_SCALE);

        if (bccomp($newApplied, '0', self::MONEY_SCALE) <= 0) {
            return;
        }

        DB::transaction(function () use ($tagihanId, $newApplied) {
            $tagihan = TagihanSiswa::query()
                ->lockForUpdate()
                ->find($tagihanId);

            if (! $tagihan) {
                return;
            }

            $sisa = bcsub(
                (string) $tagihan->total_tagihan,
                (string) $tagihan->total_terbayar,
                self::MONEY_SCALE,
            );

            if (bccomp($newApplied, $sisa, self::MONEY_SCALE) > 0) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Jumlah bayar melebihi sisa tagihan yang tersedia.',
                ]);
            }
        });
    }

    /**
     * Reconcile this payment against its tagihan using applied_amount as the
     * source of truth. Reverses any previously applied amount (including from a
     * tagihan reassignment) and applies the current amount when the payment is
     * in a state that should be applied (status berhasil and not soft-deleted).
     *
     * Setelah tagihan dikunci (lockForUpdate), validasi otoritatif overpayment
     * dilakukan menggunakan bcmath agar dua kasir yang membayar tagihan yang sama
     * secara paralel tidak dapat melampaui sisa tagihan.
     *
     * @throws ValidationException
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

            // Validasi otoritatif overpayment: setelah lock diperoleh, hitung
            // sisa tagihan yang sesungguhnya lalu periksa apakah pembayaran baru
            // akan melebihinya. Kredit lama (oldApplied) pada tagihan yang sama
            // dihitung kembali sebagai ruang yang tersedia (edit pembayaran).
            // Dilewati bila skipOverpayValidation aktif (jalur BuktiTransfer — lihat #45).
            if (
                ! static::$skipOverpayValidation &&
                $newTagihanId &&
                bccomp($newApplied, '0', self::MONEY_SCALE) > 0
            ) {
                $newTagihan = $tagihans->get($newTagihanId);

                if ($newTagihan) {
                    $currentTerbayar = (string) $newTagihan->total_terbayar;

                    // Bila ini adalah pembayaran yang sudah ada pada tagihan yang
                    // sama, kembalikan dulu applied_amount lama sebagai ruang.
                    $creditBack = ($oldTagihanId === $newTagihanId)
                        ? $oldApplied
                        : '0';

                    $silakanDibayar = bcadd(
                        bcsub((string) $newTagihan->total_tagihan, $currentTerbayar, self::MONEY_SCALE),
                        $creditBack,
                        self::MONEY_SCALE,
                    );

                    if (bccomp($newApplied, $silakanDibayar, self::MONEY_SCALE) > 0) {
                        throw ValidationException::withMessages([
                            'jumlah_bayar' => 'Jumlah bayar melebihi sisa tagihan yang tersedia.',
                        ]);
                    }
                }
            }

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
