<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiTagihan extends Model
{
    use HasFactory;

    protected $fillable = [
        'tagihan_siswa_id',
        'siswa_id',
        'tujuan_nomor',
        'pesan',
        'status',
        'driver',
        'response',
        'dikirim_oleh',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TagihanSiswa, NotifikasiTagihan>
     */
    public function tagihanSiswa(): BelongsTo
    {
        return $this->belongsTo(TagihanSiswa::class);
    }

    /**
     * @return BelongsTo<Siswa, NotifikasiTagihan>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * @return BelongsTo<User, NotifikasiTagihan>
     */
    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikirim_oleh');
    }

    /**
     * Label dan warna status notifikasi.
     *
     * @return array{label: string, color: string}
     */
    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'antri' => ['label' => 'Antri', 'color' => 'warning'],
            'terkirim' => ['label' => 'Terkirim', 'color' => 'success'],
            'gagal' => ['label' => 'Gagal', 'color' => 'danger'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }
}
