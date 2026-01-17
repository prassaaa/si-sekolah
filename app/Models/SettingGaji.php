<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SettingGaji extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'pegawai_id',
        'gaji_pokok',
        'tunjangan_jabatan',
        'tunjangan_kehadiran',
        'tunjangan_transport',
        'tunjangan_makan',
        'tunjangan_lainnya',
        'potongan_bpjs',
        'potongan_pph21',
        'potongan_lainnya',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'gaji_pokok' => 'decimal:2',
            'tunjangan_jabatan' => 'decimal:2',
            'tunjangan_kehadiran' => 'decimal:2',
            'tunjangan_transport' => 'decimal:2',
            'tunjangan_makan' => 'decimal:2',
            'tunjangan_lainnya' => 'decimal:2',
            'potongan_bpjs' => 'decimal:2',
            'potongan_pph21' => 'decimal:2',
            'potongan_lainnya' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['pegawai_id', 'gaji_pokok', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('setting_gaji');
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function slipGajis(): HasMany
    {
        return $this->hasMany(SlipGaji::class);
    }

    public function getTotalTunjanganAttribute(): float
    {
        return $this->tunjangan_jabatan + $this->tunjangan_kehadiran +
               $this->tunjangan_transport + $this->tunjangan_makan +
               $this->tunjangan_lainnya;
    }

    public function getTotalPotonganAttribute(): float
    {
        return $this->potongan_bpjs + $this->potongan_pph21 + $this->potongan_lainnya;
    }

    public function getGajiBersihAttribute(): float
    {
        return $this->gaji_pokok + $this->total_tunjangan - $this->total_potongan;
    }
}
