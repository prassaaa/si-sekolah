<?php

namespace App\Models;

use Database\Factories\KartuRfidFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class KartuRfid extends Model
{
    /** @use HasFactory<KartuRfidFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'owner_type',
        'owner_id',
        'uid',
        'status',
        'diaktifkan_pada',
        'dinonaktifkan_pada',
        'keterangan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'diaktifkan_pada' => 'datetime',
            'dinonaktifkan_pada' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<RfidScanLog, $this>
     */
    public function scanLogs(): HasMany
    {
        return $this->hasMany(RfidScanLog::class);
    }

    /**
     * Mutator: normalisasi UID ke format kanonikal (uppercase, tanpa separator).
     * Terima input apapun (lowercase, dgn `:` atau `-`), simpan sebagai hex uppercase.
     */
    public function setUidAttribute(string $value): void
    {
        $normalized = strtoupper((string) preg_replace('/[^0-9A-Fa-f]/', '', $value));

        if (! preg_match('/^[0-9A-F]{8,20}$/', $normalized)) {
            throw new InvalidArgumentException("UID tidak valid: {$value}");
        }

        $this->attributes['uid'] = $normalized;
    }

    /**
     * @param  Builder<KartuRfid>  $query
     * @return Builder<KartuRfid>
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    /**
     * @param  Builder<KartuRfid>  $query
     * @return Builder<KartuRfid>
     */
    public function scopeByUid(Builder $query, string $uid): Builder
    {
        $normalized = strtoupper((string) preg_replace('/[^0-9A-Fa-f]/', '', $uid));

        return $query->where('uid', $normalized);
    }

    public function nonaktifkan(?string $alasan = null): void
    {
        $this->update([
            'status' => 'nonaktif',
            'dinonaktifkan_pada' => now(),
            'keterangan' => $alasan ?? $this->keterangan,
        ]);
    }

    public function tandaiHilang(?string $alasan = null): void
    {
        $this->update([
            'status' => 'hilang',
            'dinonaktifkan_pada' => now(),
            'keterangan' => $alasan ?? $this->keterangan,
        ]);
    }

    /**
     * @return array{label: string, color: string}
     */
    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'aktif' => ['label' => 'Aktif', 'color' => 'success'],
            'nonaktif' => ['label' => 'Nonaktif', 'color' => 'gray'],
            'hilang' => ['label' => 'Hilang', 'color' => 'danger'],
            'rusak' => ['label' => 'Rusak', 'color' => 'warning'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }

    protected static function booted(): void
    {
        static::creating(function (KartuRfid $kartu): void {
            if ($kartu->status === 'aktif' && $kartu->owner_id && $kartu->owner_type) {
                static::query()
                    ->where('owner_type', $kartu->owner_type)
                    ->where('owner_id', $kartu->owner_id)
                    ->where('status', 'aktif')
                    ->update([
                        'status' => 'nonaktif',
                        'dinonaktifkan_pada' => now(),
                    ]);
            }
        });

        static::updating(function (KartuRfid $kartu): void {
            $ownershipChanged = $kartu->isDirty('owner_id') || $kartu->isDirty('owner_type');

            if (! $kartu->isDirty('status') && ! $ownershipChanged) {
                return;
            }

            if ($kartu->status === 'aktif' && $kartu->owner_id && $kartu->owner_type) {
                static::query()
                    ->where('owner_type', $kartu->owner_type)
                    ->where('owner_id', $kartu->owner_id)
                    ->where('id', '!=', $kartu->id)
                    ->where('status', 'aktif')
                    ->update([
                        'status' => 'nonaktif',
                        'dinonaktifkan_pada' => now(),
                    ]);
            }
        });
    }
}
