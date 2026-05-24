<?php

namespace App\Models;

use Database\Factories\PresensiHarianFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PresensiHarian extends Model
{
    /** @use HasFactory<PresensiHarianFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'siswa_id',
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
     * @return BelongsTo<Siswa, $this>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    /**
     * @param  Builder<PresensiHarian>  $query
     * @return Builder<PresensiHarian>
     */
    public function scopeHariIni(Builder $query): Builder
    {
        return $query->whereDate('tanggal', today());
    }

    /**
     * @param  Builder<PresensiHarian>  $query
     * @return Builder<PresensiHarian>
     */
    public function scopeBulanIni(Builder $query): Builder
    {
        return $query
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month);
    }

    /**
     * @param  Builder<PresensiHarian>  $query
     * @return Builder<PresensiHarian>
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<PresensiHarian>  $query
     * @return Builder<PresensiHarian>
     */
    public function scopeTerlambatSaja(Builder $query): Builder
    {
        return $query->where('status', 'terlambat');
    }

    public function isHadir(): bool
    {
        return in_array($this->status, ['hadir', 'terlambat'], true);
    }

    public function isTerlambat(): bool
    {
        return $this->status === 'terlambat';
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
