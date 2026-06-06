<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
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

    /**
     * Only one active setting per pegawai is expected.
     * Use activate() to switch the active setting for a pegawai.
     */
    public function scopeActiveForPegawai(Builder $query, int $pegawaiId): Builder
    {
        return $query->where('pegawai_id', $pegawaiId)->where('is_active', true);
    }

    /**
     * Activate this setting and deactivate all other settings for the same pegawai
     * in a single transaction to preserve the one-active-per-pegawai invariant.
     */
    public function activate(): void
    {
        DB::transaction(function () {
            self::where('pegawai_id', $this->pegawai_id)
                ->where('id', '!=', $this->id)
                ->update(['is_active' => false]);
            $this->update(['is_active' => true]);
        });
    }

    /**
     * Total tunjangan computed with bcmath to avoid float precision errors on decimal strings.
     */
    public function getTotalTunjanganAttribute(): string
    {
        $total = '0.00';
        foreach ([
            $this->tunjangan_jabatan,
            $this->tunjangan_kehadiran,
            $this->tunjangan_transport,
            $this->tunjangan_makan,
            $this->tunjangan_lainnya,
        ] as $value) {
            $total = bcadd($total, (string) ($value ?? '0'), 2);
        }

        return $total;
    }

    /**
     * Total potongan computed with bcmath to avoid float precision errors on decimal strings.
     */
    public function getTotalPotonganAttribute(): string
    {
        $total = '0.00';
        foreach ([
            $this->potongan_bpjs,
            $this->potongan_pph21,
            $this->potongan_lainnya,
        ] as $value) {
            $total = bcadd($total, (string) ($value ?? '0'), 2);
        }

        return $total;
    }

    /**
     * Gaji bersih computed with bcmath: gaji_pokok + total_tunjangan - total_potongan.
     */
    public function getGajiBersihAttribute(): string
    {
        return bcsub(
            bcadd((string) ($this->gaji_pokok ?? '0'), $this->total_tunjangan, 2),
            $this->total_potongan,
            2,
        );
    }
}
