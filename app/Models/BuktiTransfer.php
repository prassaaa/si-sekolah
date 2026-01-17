<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BuktiTransfer extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'siswa_id',
        'tagihan_siswa_id',
        'nama_pengirim',
        'bank_pengirim',
        'nomor_rekening',
        'nominal',
        'tanggal_transfer',
        'bukti_file',
        'status',
        'catatan_wali',
        'catatan_admin',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'tanggal_transfer' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['siswa_id', 'tagihan_siswa_id', 'nominal', 'status', 'verified_by'])
            ->logOnlyDirty()
            ->useLogName('bukti_transfer');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function tagihanSiswa(): BelongsTo
    {
        return $this->belongsTo(TagihanSiswa::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
