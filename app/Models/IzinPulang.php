<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class IzinPulang extends Model
{
    /** @use HasFactory<\Database\Factories\IzinPulangFactory> */
    use HasFactory;

    use LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'siswa_id',
        'tanggal',
        'jam_pulang',
        'alasan',
        'kategori',
        'penjemput_nama',
        'penjemput_hubungan',
        'penjemput_telepon',
        'petugas_id',
        'status',
        'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_pulang' => 'datetime:H:i',
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
     * @return BelongsTo<Siswa, IzinPulang>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * @return BelongsTo<Pegawai, IzinPulang>
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'petugas_id');
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<IzinPulang>  $query
     * @return \Illuminate\Database\Eloquent\Builder<IzinPulang>
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<IzinPulang>  $query
     * @return \Illuminate\Database\Eloquent\Builder<IzinPulang>
     */
    public function scopeKategori($query, string $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<IzinPulang>  $query
     * @return \Illuminate\Database\Eloquent\Builder<IzinPulang>
     */
    public function scopeHariIni($query)
    {
        return $query->whereDate('tanggal', today());
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Status info dengan warna
     *
     * @return array{label: string, color: string}
     */
    public function getStatusInfoAttribute(): array
    {
        $statusMap = [
            'diizinkan' => ['label' => 'Diizinkan', 'color' => 'success'],
            'ditolak' => ['label' => 'Ditolak', 'color' => 'danger'],
            'pending' => ['label' => 'Pending', 'color' => 'warning'],
        ];

        return $statusMap[$this->status] ?? ['label' => $this->status, 'color' => 'gray'];
    }

    /**
     * Kategori info
     *
     * @return array{label: string, color: string}
     */
    public function getKategoriInfoAttribute(): array
    {
        $kategoriMap = [
            'sakit' => ['label' => 'Sakit', 'color' => 'danger'],
            'kepentingan_keluarga' => ['label' => 'Kepentingan Keluarga', 'color' => 'info'],
            'urusan_pribadi' => ['label' => 'Urusan Pribadi', 'color' => 'warning'],
            'lainnya' => ['label' => 'Lainnya', 'color' => 'gray'],
        ];

        return $kategoriMap[$this->kategori] ?? ['label' => $this->kategori, 'color' => 'gray'];
    }
}
