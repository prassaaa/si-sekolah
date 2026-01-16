<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Konseling extends Model
{
    /** @use HasFactory<\Database\Factories\KonselingFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = [
        'siswa_id',
        'semester_id',
        'konselor_id',
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'jenis',
        'kategori',
        'permasalahan',
        'hasil_konseling',
        'rekomendasi',
        'status',
        'perlu_tindak_lanjut',
        'tanggal_tindak_lanjut',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tanggal_tindak_lanjut' => 'date',
            'waktu_mulai' => 'datetime:H:i',
            'waktu_selesai' => 'datetime:H:i',
            'perlu_tindak_lanjut' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function konselor(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'konselor_id');
    }

    public function getStatusInfoAttribute(): array
    {
        $map = [
            'dijadwalkan' => ['label' => 'Dijadwalkan', 'color' => 'info'],
            'berlangsung' => ['label' => 'Berlangsung', 'color' => 'warning'],
            'selesai' => ['label' => 'Selesai', 'color' => 'success'],
            'batal' => ['label' => 'Batal', 'color' => 'gray'],
        ];

        return $map[$this->status] ?? ['label' => $this->status, 'color' => 'gray'];
    }

    public function getKategoriInfoAttribute(): array
    {
        $map = [
            'akademik' => ['label' => 'Akademik', 'color' => 'info'],
            'pribadi' => ['label' => 'Pribadi', 'color' => 'warning'],
            'sosial' => ['label' => 'Sosial', 'color' => 'success'],
            'karir' => ['label' => 'Karir', 'color' => 'primary'],
            'lainnya' => ['label' => 'Lainnya', 'color' => 'gray'],
        ];

        return $map[$this->kategori] ?? ['label' => $this->kategori, 'color' => 'gray'];
    }
}
