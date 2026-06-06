<?php

namespace App\Models;

use Database\Factories\SarprasBarangFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SarprasBarang extends Model
{
    /** @use HasFactory<SarprasBarangFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'kode_inventaris',
        'nama',
        'sarpras_kategori_id',
        'ruangan_id',
        'tipe',
        'merk',
        'spesifikasi',
        'kondisi',
        'status',
        'sumber_dana',
        'tahun_perolehan',
        'harga_perolehan',
        'jumlah',
        'satuan',
        'foto',
        'keterangan',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun_perolehan' => 'integer',
            'harga_perolehan' => 'decimal:2',
            'jumlah' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return BelongsTo<SarprasKategori, $this>
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(SarprasKategori::class, 'sarpras_kategori_id');
    }

    /**
     * @return BelongsTo<Ruangan, $this>
     */
    public function ruangan(): BelongsTo
    {
        return $this->belongsTo(Ruangan::class);
    }

    /**
     * @return HasMany<SarprasPeminjaman, $this>
     */
    public function peminjamans(): HasMany
    {
        return $this->hasMany(SarprasPeminjaman::class);
    }

    /**
     * @return HasMany<SarprasPemeliharaan, $this>
     */
    public function pemeliharaans(): HasMany
    {
        return $this->hasMany(SarprasPemeliharaan::class);
    }

    /**
     * @return HasMany<SarprasPenghapusan, $this>
     */
    public function penghapusans(): HasMany
    {
        return $this->hasMany(SarprasPenghapusan::class);
    }

    /**
     * @param  Builder<SarprasBarang>  $query
     * @return Builder<SarprasBarang>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<SarprasBarang>  $query
     * @return Builder<SarprasBarang>
     */
    public function scopeTersedia(Builder $query): Builder
    {
        return $query->where('status', 'tersedia');
    }

    public function isAset(): bool
    {
        return $this->tipe === 'aset';
    }

    public function isBahan(): bool
    {
        return $this->tipe === 'bahan';
    }

    /**
     * @return array{label: string, color: string}
     */
    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'tersedia' => ['label' => 'Tersedia', 'color' => 'success'],
            'dipinjam' => ['label' => 'Dipinjam', 'color' => 'warning'],
            'perbaikan' => ['label' => 'Perbaikan', 'color' => 'info'],
            'dihapus' => ['label' => 'Dihapus', 'color' => 'danger'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }

    /**
     * @return array{label: string, color: string}
     */
    public function getKondisiInfoAttribute(): array
    {
        return match ($this->kondisi) {
            'baik' => ['label' => 'Baik', 'color' => 'success'],
            'rusak_ringan' => ['label' => 'Rusak Ringan', 'color' => 'warning'],
            'rusak_berat' => ['label' => 'Rusak Berat', 'color' => 'danger'],
            default => ['label' => $this->kondisi, 'color' => 'gray'],
        };
    }
}
