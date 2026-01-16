<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Akun extends Model
{
    /** @use HasFactory<\Database\Factories\AkunFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'kode',
        'nama',
        'tipe',
        'kategori',
        'parent_id',
        'deskripsi',
        'saldo_awal',
        'saldo_akhir',
        'posisi_normal',
        'is_active',
        'level',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'saldo_awal' => 'decimal:2',
            'saldo_akhir' => 'decimal:2',
            'is_active' => 'boolean',
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
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Akun::class, 'parent_id');
    }

    /**
     * @return HasMany<Akun, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Akun::class, 'parent_id');
    }

    /**
     * @return HasMany<JurnalUmum, $this>
     */
    public function jurnalUmums(): HasMany
    {
        return $this->hasMany(JurnalUmum::class);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Akun>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Akun>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Akun>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Akun>
     */
    public function scopeByTipe($query, string $tipe)
    {
        return $query->where('tipe', $tipe);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Akun>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Akun>
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * @return array<string, string>
     */
    public function getTipeInfoAttribute(): array
    {
        return match ($this->tipe) {
            'aset' => ['label' => 'Aset', 'color' => 'info'],
            'liabilitas' => ['label' => 'Liabilitas', 'color' => 'warning'],
            'ekuitas' => ['label' => 'Ekuitas', 'color' => 'success'],
            'pendapatan' => ['label' => 'Pendapatan', 'color' => 'primary'],
            'beban' => ['label' => 'Beban', 'color' => 'danger'],
            default => ['label' => $this->tipe, 'color' => 'gray'],
        };
    }
}
