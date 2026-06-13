<?php

namespace App\Models;

use App\Services\Accounting\PeriodeGuard;
use Database\Factories\PeriodeAkuntansiFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Periode akuntansi (bulan/tahun) yang dapat dikunci (tutup buku) agar
 * laporan yang telah diserahkan tidak berubah retroaktif.
 *
 * Saat status 'closed', tidak ada transaksi (jurnal, kas, pembayaran,
 * tabungan) bertanggal pada periode tersebut yang boleh dibuat, diubah,
 * atau dihapus. Penjagaan ini diterapkan terpusat oleh
 * {@see PeriodeGuard}.
 */
class PeriodeAkuntansi extends Model
{
    /** @use HasFactory<PeriodeAkuntansiFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'tahun',
        'bulan',
        'status',
        'closed_by',
        'closed_at',
        'keterangan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'closed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tahun', 'bulan', 'status', 'closed_by', 'closed_at', 'keterangan'])
            ->logOnlyDirty()
            ->useLogName('periode_akuntansi');
    }

    /**
     * Pengguna yang menutup periode ini.
     *
     * @return BelongsTo<User, $this>
     */
    public function penutup(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * @param  Builder<PeriodeAkuntansi>  $query
     * @return Builder<PeriodeAkuntansi>
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    /**
     * Mengembalikan true bila periode (bulan/tahun) telah ditutup.
     */
    public static function isClosed(int $tahun, int $bulan): bool
    {
        return static::query()
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->where('status', 'closed')
            ->exists();
    }

    /**
     * Mengembalikan true bila periode ini berstatus tertutup.
     */
    public function isTertutup(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Nama bulan dalam Bahasa Indonesia (1 = Januari ... 12 = Desember).
     */
    public function getNamaBulanAttribute(): string
    {
        return static::namaBulan((int) $this->bulan);
    }

    /**
     * Label periode yang mudah dibaca, mis. "Juli 2026".
     */
    public function getLabelPeriodeAttribute(): string
    {
        return static::namaBulan((int) $this->bulan).' '.$this->tahun;
    }

    /**
     * Pemetaan nomor bulan ke nama bulan Bahasa Indonesia.
     */
    public static function namaBulan(int $bulan): string
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ][$bulan] ?? (string) $bulan;
    }
}
