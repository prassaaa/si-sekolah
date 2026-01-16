<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Kelulusan extends Model
{
    /** @use HasFactory<\Database\Factories\KelulusanFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'siswa_id',
        'tahun_ajaran_id',
        'nomor_ijazah',
        'nomor_skhun',
        'tanggal_lulus',
        'status',
        'nilai_akhir',
        'predikat',
        'tujuan_sekolah',
        'catatan',
        'disetujui_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lulus' => 'date',
            'nilai_akhir' => 'decimal:2',
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

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'disetujui_oleh');
    }

    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'lulus' => ['label' => 'Lulus', 'color' => 'success'],
            'tidak_lulus' => ['label' => 'Tidak Lulus', 'color' => 'danger'],
            'pending' => ['label' => 'Pending', 'color' => 'gray'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }

    public function getPredikatInfoAttribute(): ?string
    {
        return match ($this->predikat) {
            'sangat_baik' => 'Sangat Baik',
            'baik' => 'Baik',
            'cukup' => 'Cukup',
            'kurang' => 'Kurang',
            default => $this->predikat,
        };
    }
}
