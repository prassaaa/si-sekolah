<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Prestasi extends Model
{
    /** @use HasFactory<\Database\Factories\PrestasiFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = [
        'siswa_id',
        'semester_id',
        'nama_prestasi',
        'tingkat',
        'jenis',
        'peringkat',
        'penyelenggara',
        'tanggal',
        'bukti',
        'keterangan',
    ];

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

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function getTingkatInfoAttribute(): array
    {
        $map = [
            'sekolah' => ['label' => 'Sekolah', 'color' => 'gray'],
            'kecamatan' => ['label' => 'Kecamatan', 'color' => 'info'],
            'kabupaten' => ['label' => 'Kabupaten', 'color' => 'primary'],
            'provinsi' => ['label' => 'Provinsi', 'color' => 'warning'],
            'nasional' => ['label' => 'Nasional', 'color' => 'success'],
            'internasional' => ['label' => 'Internasional', 'color' => 'danger'],
        ];

        return $map[$this->tingkat] ?? ['label' => $this->tingkat, 'color' => 'gray'];
    }

    public function getJenisInfoAttribute(): array
    {
        $map = [
            'akademik' => ['label' => 'Akademik', 'color' => 'info'],
            'non_akademik' => ['label' => 'Non Akademik', 'color' => 'warning'],
            'olahraga' => ['label' => 'Olahraga', 'color' => 'success'],
            'seni' => ['label' => 'Seni', 'color' => 'danger'],
            'keagamaan' => ['label' => 'Keagamaan', 'color' => 'primary'],
            'lainnya' => ['label' => 'Lainnya', 'color' => 'gray'],
        ];

        return $map[$this->jenis] ?? ['label' => $this->jenis, 'color' => 'gray'];
    }
}
