<?php

namespace App\Models;

use App\Observers\KasMasukObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy(KasMasukObserver::class)]
class KasMasuk extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nomor_bukti',
        'akun_id',
        'kas_akun_id',
        'tanggal',
        'nominal',
        'sumber',
        'keterangan',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'tanggal' => 'date',
            'kas_akun_id' => 'integer',
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
                'sumber',
                'keterangan',
            ])
            ->logOnlyDirty()
            ->useLogName('kas_masuk');
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
        $prefix = 'KM-'.date('Ymd');

        $lastNomor = DB::table('kas_masuks')
            ->where('nomor_bukti', 'like', $prefix.'%')
            ->lockForUpdate()
            ->max('nomor_bukti');

        $lastNumber = $lastNomor ? (int) substr($lastNomor, -4) : 0;

        return $prefix.'-'.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    protected static function booted(): void
    {
        static::creating(function (KasMasuk $kasMasuk) {
            if (! $kasMasuk->user_id) {
                $kasMasuk->user_id = auth()->id();
            }
            if (empty($kasMasuk->nomor_bukti)) {
                $kasMasuk->nomor_bukti = static::generateNomorBukti();
            }

            // Validasi awal: akun lawan tidak boleh sama dengan akun kas
            if (
                $kasMasuk->akun_id !== null
                && $kasMasuk->kas_akun_id !== null
                && (int) $kasMasuk->akun_id === (int) $kasMasuk->kas_akun_id
            ) {
                throw ValidationException::withMessages([
                    'akun_id' => 'Akun lawan tidak boleh sama dengan Akun Kas/Bank yang dipilih.',
                ]);
            }
        });
    }
}
