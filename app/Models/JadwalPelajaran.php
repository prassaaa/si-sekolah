<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JadwalPelajaran extends Model
{
    /** @use HasFactory<\Database\Factories\JadwalPelajaranFactory> */
    use HasFactory;

    use LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'semester_id',
        'kelas_id',
        'mata_pelajaran_id',
        'jam_pelajaran_id',
        'guru_id',
        'hari',
        'keterangan',
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

    // =====================
    // RELATIONSHIPS
    // =====================

    /**
     * @return BelongsTo<Semester, JadwalPelajaran>
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * @return BelongsTo<Kelas, JadwalPelajaran>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * @return BelongsTo<MataPelajaran, JadwalPelajaran>
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    /**
     * @return BelongsTo<JamPelajaran, JadwalPelajaran>
     */
    public function jamPelajaran(): BelongsTo
    {
        return $this->belongsTo(JamPelajaran::class);
    }

    /**
     * @return BelongsTo<Pegawai, JadwalPelajaran>
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'guru_id');
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<JadwalPelajaran>  $query
     * @return \Illuminate\Database\Eloquent\Builder<JadwalPelajaran>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<JadwalPelajaran>  $query
     * @return \Illuminate\Database\Eloquent\Builder<JadwalPelajaran>
     */
    public function scopeHari($query, string $hari)
    {
        return $query->where('hari', $hari);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<JadwalPelajaran>  $query
     * @return \Illuminate\Database\Eloquent\Builder<JadwalPelajaran>
     */
    public function scopeForKelas($query, int $kelasId)
    {
        return $query->where('kelas_id', $kelasId);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<JadwalPelajaran>  $query
     * @return \Illuminate\Database\Eloquent\Builder<JadwalPelajaran>
     */
    public function scopeForGuru($query, int $guruId)
    {
        return $query->where('guru_id', $guruId);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<JadwalPelajaran>  $query
     * @return \Illuminate\Database\Eloquent\Builder<JadwalPelajaran>
     */
    public function scopeOrdered($query)
    {
        return $query
            ->orderByRaw(
                "FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')",
            )
            ->orderBy('jam_pelajaran_id');
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Format jadwal: Hari, Jam - Mata Pelajaran
     */
    public function getJadwalLengkapAttribute(): string
    {
        return sprintf(
            '%s, %s - %s',
            $this->hari,
            $this->jamPelajaran?->nama ?? '-',
            $this->mataPelajaran?->nama ?? '-',
        );
    }

    /**
     * Format waktu dari JamPelajaran
     */
    public function getWaktuAttribute(): string
    {
        if (! $this->jamPelajaran) {
            return '-';
        }

        return $this->jamPelajaran->waktu_mulai.
            ' - '.
            $this->jamPelajaran->waktu_selesai;
    }

    /**
     * List hari untuk dropdown
     *
     * @return array<string, string>
     */
    public static function hariOptions(): array
    {
        return [
            'Senin' => 'Senin',
            'Selasa' => 'Selasa',
            'Rabu' => 'Rabu',
            'Kamis' => 'Kamis',
            'Jumat' => 'Jumat',
            'Sabtu' => 'Sabtu',
        ];
    }
}
