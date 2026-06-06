<?php

namespace App\Models;

use Database\Factories\JurnalUmumFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JurnalUmum extends Model
{
    /** @use HasFactory<JurnalUmumFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nomor_bukti',
        'tanggal',
        'keterangan',
        'akun_id',
        'debit',
        'kredit',
        'referensi',
        'jenis_referensi',
        'referensi_id',
        'created_by',
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
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    /**
     * @return BelongsTo<Akun, $this>
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Polymorphic source document (e.g. SlipGaji, pembayaran, etc.).
     * The morph type is stored in `jenis_referensi` and the FK in `referensi_id`.
     * Register a morphMap in a ServiceProvider to map string keys to model classes.
     *
     * @return MorphTo<Model, $this>
     */
    public function referensiModel(): MorphTo
    {
        return $this->morphTo('referensiModel', 'jenis_referensi', 'referensi_id');
    }

    /**
     * @param  Builder<JurnalUmum>  $query
     * @return Builder<JurnalUmum>
     */
    public function scopeByPeriode($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    /**
     * @param  Builder<JurnalUmum>  $query
     * @return Builder<JurnalUmum>
     */
    public function scopeDebitOnly($query)
    {
        return $query->where('debit', '>', 0);
    }

    /**
     * @param  Builder<JurnalUmum>  $query
     * @return Builder<JurnalUmum>
     */
    public function scopeKreditOnly($query)
    {
        return $query->where('kredit', '>', 0);
    }
}
