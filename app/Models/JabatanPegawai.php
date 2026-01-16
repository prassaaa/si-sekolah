<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JabatanPegawai extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'jabatan_pegawais';

    protected $fillable = [
        'kode',
        'nama',
        'jenis',
        'golongan',
        'gaji_pokok',
        'tunjangan',
        'deskripsi',
        'urutan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'gaji_pokok' => 'decimal:2',
            'tunjangan' => 'decimal:2',
            'urutan' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['kode', 'nama', 'jenis', 'golongan', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function pegawais(): HasMany
    {
        return $this->hasMany(Pegawai::class, 'jabatan_id');
    }

    public function getTotalGajiAttribute(): float
    {
        return (float) $this->gaji_pokok + (float) $this->tunjangan;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('urutan')->orderBy('nama');
    }
}
