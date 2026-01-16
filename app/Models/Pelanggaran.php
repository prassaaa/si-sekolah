<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pelanggaran extends Model
{
    /** @use HasFactory<\Database\Factories\PelanggaranFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = [
        'siswa_id',
        'semester_id',
        'tanggal',
        'jenis_pelanggaran',
        'kategori',
        'poin',
        'deskripsi',
        'bukti',
        'pelapor_id',
        'status',
        'tindak_lanjut',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'poin' => 'integer',
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

    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'pelapor_id');
    }

    public function getKategoriInfoAttribute(): array
    {
        $map = [
            'ringan' => ['label' => 'Ringan', 'color' => 'warning'],
            'sedang' => ['label' => 'Sedang', 'color' => 'info'],
            'berat' => ['label' => 'Berat', 'color' => 'danger'],
        ];

        return $map[$this->kategori] ?? ['label' => $this->kategori, 'color' => 'gray'];
    }

    public function getStatusInfoAttribute(): array
    {
        $map = [
            'proses' => ['label' => 'Proses', 'color' => 'warning'],
            'selesai' => ['label' => 'Selesai', 'color' => 'success'],
            'batal' => ['label' => 'Batal', 'color' => 'gray'],
        ];

        return $map[$this->status] ?? ['label' => $this->status, 'color' => 'gray'];
    }
}
