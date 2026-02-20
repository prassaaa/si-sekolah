<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pembayaran extends Model
{
    /** @use HasFactory<\Database\Factories\PembayaranFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tagihan_siswa_id',
        'nomor_transaksi',
        'tanggal_bayar',
        'jumlah_bayar',
        'metode_pembayaran',
        'referensi_pembayaran',
        'diterima_oleh',
        'unit_pos_id',
        'keterangan',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_bayar' => 'date',
            'jumlah_bayar' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }

    public function tagihanSiswa(): BelongsTo
    {
        return $this->belongsTo(TagihanSiswa::class);
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'diterima_oleh');
    }

    public function unitPos(): BelongsTo
    {
        return $this->belongsTo(UnitPos::class);
    }

    public function getMetodeInfoAttribute(): string
    {
        return match ($this->metode_pembayaran) {
            'tunai' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'virtual_account' => 'Virtual Account',
            'lainnya' => 'Lainnya',
            default => $this->metode_pembayaran,
        };
    }

    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'pending' => ['label' => 'Pending', 'color' => 'warning'],
            'berhasil' => ['label' => 'Berhasil', 'color' => 'success'],
            'gagal' => ['label' => 'Gagal', 'color' => 'danger'],
            'batal' => ['label' => 'Batal', 'color' => 'gray'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }

    protected static function booted(): void
    {
        static::created(function (Pembayaran $pembayaran) {
            if ($pembayaran->status === 'berhasil') {
                $tagihan = $pembayaran->tagihanSiswa;
                $tagihan->increment('total_terbayar', $pembayaran->jumlah_bayar);
                $tagihan->decrement('sisa_tagihan', $pembayaran->jumlah_bayar);
                $tagihan->refresh();
                $tagihan->updateStatus();
            }
        });
    }
}
