<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Sekolah extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'sekolahs';

    protected $fillable = [
        'npsn',
        'nama',
        'nama_yayasan',
        'jenjang',
        'status',
        'alamat',
        'kelurahan',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'kode_pos',
        'telepon',
        'fax',
        'email',
        'website',
        'kepala_sekolah',
        'nip_kepala_sekolah',
        'logo',
        'visi',
        'misi',
        'tahun_berdiri',
        'akreditasi',
        'tanggal_akreditasi',
        'no_sk_operasional',
        'tanggal_sk_operasional',
        'is_active',
        'jam_masuk_default',
        'batas_terlambat_menit',
        'jam_pulang_minimal',
        'debounce_scan_detik',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_akreditasi' => 'date',
            'tanggal_sk_operasional' => 'date',
            'tahun_berdiri' => 'integer',
            'is_active' => 'boolean',
            'jam_masuk_default' => 'datetime:H:i:s',
            'jam_pulang_minimal' => 'datetime:H:i:s',
            'batas_terlambat_menit' => 'integer',
            'debounce_scan_detik' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['npsn', 'nama', 'kepala_sekolah', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
