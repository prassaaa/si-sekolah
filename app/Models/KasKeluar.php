<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KasKeluar extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nomor_bukti',
        'akun_id',
        'tanggal',
        'nominal',
        'penerima',
        'keterangan',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'nomor_bukti',
                'akun_id',
                'tanggal',
                'nominal',
                'penerima',
                'keterangan',
            ])
            ->logOnlyDirty()
            ->useLogName('kas_keluar');
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateNomorBukti(): string
    {
        $prefix = 'KK-'.date('Ymd');

        $lastNomor = DB::table('kas_keluars')
            ->where('nomor_bukti', 'like', $prefix.'%')
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
        });
    }
}
