<?php

namespace App\Models;

use Database\Factories\PresensiHarianPegawaiFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PresensiHarianPegawai extends Model
{
    /** @use HasFactory<PresensiHarianPegawaiFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'presensi_harian_pegawais';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'pegawai_id',
        'tanggal',
        'jam_masuk',
        'jam_pulang',
        'status',
        'sumber_masuk',
        'sumber_pulang',
        'terlambat_menit',
        'keterangan',
        'dicatat_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_masuk' => 'datetime:H:i:s',
            'jam_pulang' => 'datetime:H:i:s',
            'terlambat_menit' => 'integer',
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
    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    /**
     * @param  Builder<PresensiHarianPegawai>  $query
     * @return Builder<PresensiHarianPegawai>
     */
    public function scopeHariIni(Builder $query): Builder
    {
        return $query->whereDate('tanggal', today());
    }

    /**
     * @param  Builder<PresensiHarianPegawai>  $query
     * @return Builder<PresensiHarianPegawai>
     */
    public function scopeBulanIni(Builder $query): Builder
    {
        return $query
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month);
    }

    /**
     * @param  Builder<PresensiHarianPegawai>  $query
     * @return Builder<PresensiHarianPegawai>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function isHadir(): bool
    {
        return in_array($this->status, ['hadir', 'terlambat'], true);
    }

    public function sudahPulang(): bool
    {
        return $this->jam_pulang !== null;
    }

    /**
     * @return array{label: string, color: string}
     */
    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'hadir' => ['label' => 'Hadir', 'color' => 'success'],
            'terlambat' => ['label' => 'Terlambat', 'color' => 'warning'],
            'izin' => ['label' => 'Izin', 'color' => 'info'],
            'sakit' => ['label' => 'Sakit', 'color' => 'gray'],
            'alpha' => ['label' => 'Alpha', 'color' => 'danger'],
            'cuti' => ['label' => 'Cuti', 'color' => 'info'],
            'dinas_luar' => ['label' => 'Dinas Luar', 'color' => 'primary'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            'hadir' => 'Hadir',
            'terlambat' => 'Terlambat',
            'izin' => 'Izin',
            'sakit' => 'Sakit',
            'alpha' => 'Alpha',
            'cuti' => 'Cuti',
            'dinas_luar' => 'Dinas Luar',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sumberOptions(): array
    {
        return [
            'rfid' => 'RFID',
            'manual' => 'Manual',
            'import' => 'Import',
        ];
    }
}
