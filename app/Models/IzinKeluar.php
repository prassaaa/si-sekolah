<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class IzinKeluar extends Model
{
    /** @use HasFactory<\Database\Factories\IzinKeluarFactory> */
    use HasFactory;

    use LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'siswa_id',
        'tanggal',
        'jam_keluar',
        'jam_kembali',
        'keperluan',
        'tujuan',
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
            'jam_keluar' => 'datetime:H:i',
            'jam_kembali' => 'datetime:H:i',
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
     * @return BelongsTo<Siswa, IzinKeluar>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * @return BelongsTo<Pegawai, IzinKeluar>
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'petugas_id');
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<IzinKeluar>  $query
     * @return \Illuminate\Database\Eloquent\Builder<IzinKeluar>
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<IzinKeluar>  $query
     * @return \Illuminate\Database\Eloquent\Builder<IzinKeluar>
     */
    public function scopeHariIni($query)
    {
        return $query->whereDate('tanggal', today());
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<IzinKeluar>  $query
     * @return \Illuminate\Database\Eloquent\Builder<IzinKeluar>
     */
    public function scopeBelumKembali($query)
    {
        return $query->whereNull('jam_kembali')
            ->where('status', 'diizinkan');
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
     * Durasi izin
     */
    public function getDurasiAttribute(): ?string
    {
        if (! $this->jam_keluar || ! $this->jam_kembali) {
            return null;
        }

        $diff = $this->jam_keluar->diff($this->jam_kembali);

        return $diff->format('%H jam %I menit');
    }
}
