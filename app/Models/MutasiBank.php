<?php

namespace App\Models;

use Database\Factories\MutasiBankFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Baris mutasi rekening koran (bank statement) untuk satu akun bank.
 *
 * Dipakai oleh fitur Rekonsiliasi Bank (F9): baris yang sudah dicocokkan
 * (`is_matched`) dianggap selaras dengan jurnal akun bank, sementara baris
 * `unmatched` adalah item outstanding sisi rekening koran.
 */
class MutasiBank extends Model
{
    /** @use HasFactory<MutasiBankFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'akun_id',
        'tanggal',
        'keterangan',
        'debit',
        'kredit',
        'saldo',
        'is_matched',
        'jurnal_umum_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'debit' => 'decimal:2',
            'kredit' => 'decimal:2',
            'saldo' => 'decimal:2',
            'is_matched' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    /**
     * Akun bank yang dimiliki baris mutasi ini.
     *
     * @return BelongsTo<Akun, $this>
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }

    /**
     * Jurnal umum yang dicocokkan ke baris mutasi ini (opsional).
     *
     * @return BelongsTo<JurnalUmum, $this>
     */
    public function jurnalUmum(): BelongsTo
    {
        return $this->belongsTo(JurnalUmum::class);
    }

    /**
     * Baris mutasi yang belum dicocokkan (outstanding sisi rekening koran).
     *
     * @param  Builder<MutasiBank>  $query
     * @return Builder<MutasiBank>
     */
    public function scopeUnmatched(Builder $query): Builder
    {
        return $query->where('is_matched', false);
    }
}
