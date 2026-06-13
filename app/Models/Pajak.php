<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pajak extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nama',
        'persentase',
        'keterangan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'persentase' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'persentase', 'keterangan', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('pajak');
    }

    /**
     * Batasi query hanya pada master pajak yang aktif.
     *
     * @param  Builder<Pajak>  $query
     * @return Builder<Pajak>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
