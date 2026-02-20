<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Absensi extends Model
{
    /** @use HasFactory<\Database\Factories\AbsensiFactory> */
    use HasFactory;

    use LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'jadwal_pelajaran_id',
        'siswa_id',
        'tanggal',
        'status',
        'keterangan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // =====================
    // RELATIONSHIPS
    // =====================

    /**
     * @return BelongsTo<JadwalPelajaran, Absensi>
     */
    public function jadwalPelajaran(): BelongsTo
    {
        return $this->belongsTo(JadwalPelajaran::class);
    }

    /**
     * @return BelongsTo<Siswa, Absensi>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Absensi>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Absensi>
     */
    public function scopeAlpha($query)
    {
        return $query->where('status', 'alpha');
    }

    // =====================
    // STATIC HELPERS
    // =====================

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            'hadir' => 'Hadir',
            'sakit' => 'Sakit',
            'izin' => 'Izin',
            'alpha' => 'Alpha',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusColors(): array
    {
        return [
            'hadir' => 'success',
            'sakit' => 'warning',
            'izin' => 'info',
            'alpha' => 'danger',
        ];
    }
}
