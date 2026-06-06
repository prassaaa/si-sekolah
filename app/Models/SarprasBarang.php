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
        'metode_susut',
        'umur_ekonomis_bulan',
        'nilai_residu',
        'tanggal_perolehan',
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
            'metode_susut' => 'string',
            'umur_ekonomis_bulan' => 'integer',
            'nilai_residu' => 'decimal:2',
            'tanggal_perolehan' => 'date',
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

    /**
     * Default economic life (in months) per kategori, keyed by kode or nama.
     *
     * @return array<string, int>
     */
    public static function defaultUmurEkonomisMap(): array
    {
        return [
            'ELK' => 48,
            'ELEKTRONIK' => 48,
            'MBL' => 96,
            'MEUBELAIR' => 96,
            'KDR' => 96,
            'KENDARAAN' => 96,
            'BGN' => 240,
            'BANGUNAN' => 240,
        ];
    }

    /**
     * Resolve the economic life in months: explicit value wins, otherwise the
     * kategori default. Returns null when no default is known (no depreciation).
     */
    public function resolveUmurEkonomisBulan(): ?int
    {
        if ($this->umur_ekonomis_bulan !== null && $this->umur_ekonomis_bulan > 0) {
            return $this->umur_ekonomis_bulan;
        }

        $kategori = $this->kategori;

        if (! $kategori) {
            return null;
        }

        $map = static::defaultUmurEkonomisMap();

        return $map[strtoupper((string) $kategori->kode)]
            ?? $map[strtoupper((string) $kategori->nama)]
            ?? null;
    }

    /**
     * Whether this item should be depreciated at all.
     */
    public function isDepreciable(): bool
    {
        return $this->metode_susut !== null
            && $this->metode_susut !== 'tanpa'
            && $this->resolveUmurEkonomisBulan() !== null;
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
