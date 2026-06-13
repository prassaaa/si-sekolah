<?php

namespace App\Models;

use App\Observers\KasKeluarObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy(KasKeluarObserver::class)]
class KasKeluar extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nomor_bukti',
        'akun_id',
        'kas_akun_id',
        'tanggal',
        'nominal',
        'penerima',
        'sumber_dana',
        'keterangan',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'tanggal' => 'date',
            'kas_akun_id' => 'integer',
            'sumber_dana' => 'string',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nomor_bukti',
                'akun_id',
                'kas_akun_id',
                'tanggal',
                'nominal',
                'penerima',
                'sumber_dana',
                'keterangan',
            ])
            ->logOnlyDirty()
            ->useLogName('kas_keluar');
    }

    /**
     * Akun lawan (pendapatan/beban) untuk entri jurnal.
     *
     * @return BelongsTo<Akun, $this>
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }

    /**
     * Akun kas/bank yang menjadi sisi kas dalam jurnal double-entry.
     *
     * @return BelongsTo<Akun, $this>
     */
    public function kasAkun(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'kas_akun_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateNomorBukti(): string
    {
        $prefix = 'KK-'.date('Ymd');

        $lastNomor = DB::table('kas_keluars')
            ->where('nomor_bukti', 'like', $prefix.'%')
            ->lockForUpdate()
            ->max('nomor_bukti');

        $lastNumber = $lastNomor ? (int) substr($lastNomor, -4) : 0;

        return $prefix.'-'.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    protected static function booted(): void
    {
        static::creating(function (KasKeluar $kasKeluar) {
            if (! $kasKeluar->user_id) {
                $kasKeluar->user_id = auth()->id();
            }
            if (empty($kasKeluar->nomor_bukti)) {
                $kasKeluar->nomor_bukti = static::generateNomorBukti();
            }

            // Validasi awal: akun lawan tidak boleh sama dengan akun kas
            if (
                $kasKeluar->akun_id !== null
                && $kasKeluar->kas_akun_id !== null
                && (int) $kasKeluar->akun_id === (int) $kasKeluar->kas_akun_id
            ) {
                throw ValidationException::withMessages([
                    'akun_id' => 'Akun lawan tidak boleh sama dengan Akun Kas/Bank yang dipilih.',
                ]);
            }
        });
    }
}
