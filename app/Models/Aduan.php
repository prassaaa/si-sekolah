<?php

namespace App\Models;

use Database\Factories\AduanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Aduan extends Model
{
    /** @use HasFactory<AduanFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'siswa_id',
        'pelapor',
        'hubungan_pelapor',
        'kontak_pelapor',
        'tanggal_aduan',
        'kategori',
        'judul',
        'isi',
        'lampiran',
        'status',
        'ditangani_oleh',
        'tanggapan',
        'tanggal_tanggapan',
        'dicatat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_aduan' => 'date',
            'tanggal_tanggapan' => 'datetime',
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

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function penangan(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'ditangani_oleh');
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * @return array{label: string, color: string}
     */
    public function getStatusInfoAttribute(): array
    {
        $map = [
            'baru' => ['label' => 'Baru', 'color' => 'danger'],
            'diproses' => ['label' => 'Diproses', 'color' => 'warning'],
            'selesai' => ['label' => 'Selesai', 'color' => 'success'],
            'ditolak' => ['label' => 'Ditolak', 'color' => 'gray'],
        ];

        return $map[$this->status] ?? ['label' => $this->status, 'color' => 'gray'];
    }

    /**
     * @return array{label: string, color: string}
     */
    public function getKategoriInfoAttribute(): array
    {
        $map = [
            'akademik' => ['label' => 'Akademik', 'color' => 'info'],
            'fasilitas' => ['label' => 'Fasilitas', 'color' => 'warning'],
            'perlakuan' => ['label' => 'Perlakuan', 'color' => 'danger'],
            'keuangan' => ['label' => 'Keuangan', 'color' => 'success'],
            'lainnya' => ['label' => 'Lainnya', 'color' => 'gray'],
        ];

        return $map[$this->kategori] ?? ['label' => $this->kategori, 'color' => 'gray'];
    }

    // =====================
    // METHODS
    // =====================

    public function tanggapi(string $tanggapan, int $pegawaiId, string $status = 'selesai'): void
    {
        $this->update([
            'tanggapan' => $tanggapan,
            'ditangani_oleh' => $pegawaiId,
            'tanggal_tanggapan' => now(),
            'status' => $status,
        ]);
    }
}
