<?php

namespace App\Models;

use Database\Factories\RuanganFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Ruangan extends Model
{
    /** @use HasFactory<RuanganFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'kode',
        'nama',
        'jenis',
        'gedung',
        'lantai',
        'kapasitas',
        'penanggung_jawab_id',
        'keterangan',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'lantai' => 'integer',
            'kapasitas' => 'integer',
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
     * @return BelongsTo<Pegawai, $this>
     */
    public function penanggungJawab(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'penanggung_jawab_id');
    }

    /**
     * @return HasMany<Kelas, $this>
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    /**
     * @return HasMany<SarprasBarang, $this>
     */
    public function barangs(): HasMany
    {
        return $this->hasMany(SarprasBarang::class);
    }

    /**
     * @param  Builder<Ruangan>  $query
     * @return Builder<Ruangan>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array{label: string, color: string}
     */
    public function getJenisInfoAttribute(): array
    {
        return match ($this->jenis) {
            'kelas' => ['label' => 'Kelas', 'color' => 'primary'],
            'lab' => ['label' => 'Laboratorium', 'color' => 'info'],
            'kantor' => ['label' => 'Kantor', 'color' => 'gray'],
            'gudang' => ['label' => 'Gudang', 'color' => 'warning'],
            'perpustakaan' => ['label' => 'Perpustakaan', 'color' => 'success'],
            'aula' => ['label' => 'Aula', 'color' => 'info'],
            'lainnya' => ['label' => 'Lainnya', 'color' => 'gray'],
            default => ['label' => $this->jenis, 'color' => 'gray'],
        };
    }
}
