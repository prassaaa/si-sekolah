<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UnitPos extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'unit_pos';

    protected $fillable = [
        'kode',
        'nama',
        'alamat',
        'telepon',
        'akun_id',
        'keterangan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['kode', 'nama', 'alamat', 'telepon', 'akun_id', 'keterangan', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('unit_pos');
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }
}
