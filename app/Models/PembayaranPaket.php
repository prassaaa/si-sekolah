<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PembayaranPaket extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nama',
        'tahun_ajaran_id',
        'total_biaya',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'total_biaya' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'tahun_ajaran_id', 'total_biaya', 'deskripsi', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('pembayaran_paket');
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function jenisPembayarans(): BelongsToMany
    {
        return $this->belongsToMany(JenisPembayaran::class, 'pembayaran_paket_items')
            ->withPivot('nominal')
            ->withTimestamps();
    }

    public function calculateTotalBiaya(): float
    {
        return $this->jenisPembayarans()->sum('pembayaran_paket_items.nominal');
    }
}
