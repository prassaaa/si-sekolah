<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KasMasuk extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nomor_bukti',
        'akun_id',
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
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nomor_bukti', 'akun_id', 'tanggal', 'nominal', 'sumber', 'keterangan'])
            ->logOnlyDirty()
            ->useLogName('kas_masuk');
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
        $prefix = 'KM-' . date('Ymd');
        $lastRecord = static::whereDate('created_at', today())->latest()->first();
        $lastNumber = $lastRecord ? (int) substr($lastRecord->nomor_bukti, -4) : 0;
        return $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    protected static function booted(): void
    {
        static::creating(function (KasMasuk $kasMasuk) {
            $kasMasuk->user_id = auth()->id();
            if (empty($kasMasuk->nomor_bukti)) {
                $kasMasuk->nomor_bukti = static::generateNomorBukti();
            }
        });
    }
}
