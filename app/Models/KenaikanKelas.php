<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KenaikanKelas extends Model
{
    /** @use HasFactory<\Database\Factories\KenaikanKelasFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'kenaikan_kelas';

    protected $fillable = [
        'siswa_id',
        'semester_id',
        'kelas_asal_id',
        'kelas_tujuan_id',
        'status',
        'nilai_rata_rata',
        'peringkat',
        'catatan',
        'tanggal_keputusan',
        'disetujui_oleh',
    ];

    protected function casts(): array
    {
        return [
            'nilai_rata_rata' => 'decimal:2',
            'peringkat' => 'integer',
            'tanggal_keputusan' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function kelasAsal(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_asal_id');
    }

    public function kelasTujuan(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_tujuan_id');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'disetujui_oleh');
    }

    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'naik' => ['label' => 'Naik Kelas', 'color' => 'success'],
            'tinggal' => ['label' => 'Tinggal Kelas', 'color' => 'danger'],
            'mutasi_keluar' => ['label' => 'Mutasi Keluar', 'color' => 'warning'],
            'pending' => ['label' => 'Pending', 'color' => 'gray'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }
}
