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

class TagihanSiswa extends Model
{
    /** @use HasFactory<\Database\Factories\TagihanSiswaFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'siswa_id',
        'jenis_pembayaran_id',
        'semester_id',
        'nomor_tagihan',
        'nominal',
        'diskon',
        'total_tagihan',
        'total_terbayar',
        'sisa_tagihan',
        'tanggal_tagihan',
        'tanggal_jatuh_tempo',
        'status',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'diskon' => 'decimal:2',
            'total_tagihan' => 'decimal:2',
            'total_terbayar' => 'decimal:2',
            'sisa_tagihan' => 'decimal:2',
            'tanggal_tagihan' => 'date',
            'tanggal_jatuh_tempo' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function jenisPembayaran(): BelongsTo
    {
        return $this->belongsTo(JenisPembayaran::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function pembayarans(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function scopeBelumLunas(Builder $query): Builder
    {
        return $query->whereIn('status', ['belum_bayar', 'sebagian']);
    }

    public function scopeLunas(Builder $query): Builder
    {
        return $query->where('status', 'lunas');
    }

    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'belum_bayar' => ['label' => 'Belum Bayar', 'color' => 'danger'],
            'sebagian' => ['label' => 'Sebagian', 'color' => 'warning'],
            'lunas' => ['label' => 'Lunas', 'color' => 'success'],
            'batal' => ['label' => 'Batal', 'color' => 'gray'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }

    public function updateStatus(): void
    {
        if ($this->sisa_tagihan <= 0) {
            $this->update(['status' => 'lunas']);
        } elseif ($this->total_terbayar > 0) {
            $this->update(['status' => 'sebagian']);
        } else {
            $this->update(['status' => 'belum_bayar']);
        }
    }
}
