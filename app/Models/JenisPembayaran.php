<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JenisPembayaran extends Model
{
    /** @use HasFactory<\Database\Factories\JenisPembayaranFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'kategori_pembayaran_id',
        'tahun_ajaran_id',
        'kode',
        'nama',
        'nominal',
        'jenis',
        'deskripsi',
        'is_active',
        'tanggal_jatuh_tempo',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'is_active' => 'boolean',
            'tanggal_jatuh_tempo' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function kategoriPembayaran(): BelongsTo
    {
        return $this->belongsTo(KategoriPembayaran::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function tagihanSiswas(): HasMany
    {
        return $this->hasMany(TagihanSiswa::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getJenisInfoAttribute(): string
    {
        return match ($this->jenis) {
            'bulanan' => 'Bulanan',
            'tahunan' => 'Tahunan',
            'sekali_bayar' => 'Sekali Bayar',
            'insidental' => 'Insidental',
            default => $this->jenis,
        };
    }
}
