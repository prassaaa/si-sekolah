<?php

namespace App\Models;

use Database\Factories\SarprasKategoriFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SarprasKategori extends Model
{
    /** @use HasFactory<SarprasKategoriFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'kode',
        'nama',
        'deskripsi',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
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
     * @return HasMany<SarprasBarang, $this>
     */
    public function barangs(): HasMany
    {
        return $this->hasMany(SarprasBarang::class);
    }

    /**
     * @return HasMany<SarprasPengadaanItem, $this>
     */
    public function pengadaanItems(): HasMany
    {
        return $this->hasMany(SarprasPengadaanItem::class);
    }

    /**
     * @param  Builder<SarprasKategori>  $query
     * @return Builder<SarprasKategori>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
