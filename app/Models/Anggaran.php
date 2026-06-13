<?php

namespace App\Models;

use Database\Factories\AnggaranFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Anggaran extends Model
{
    /** @use HasFactory<AnggaranFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tahun_ajaran_id',
        'akun_id',
        'nominal_anggaran',
        'keterangan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nominal_anggaran' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }

    /**
     * @return BelongsTo<TahunAjaran, $this>
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * @return BelongsTo<Akun, $this>
     */
    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }
}
