<?php

namespace App\Models;

use Database\Factories\RfidScanLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RfidScanLog extends Model
{
    /** @use HasFactory<RfidScanLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uid',
        'kartu_rfid_id',
        'owner_type',
        'owner_id',
        'rfid_device_id',
        'jenis',
        'pesan',
        'request_payload',
        'response_payload',
        'scanned_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'scanned_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<KartuRfid, $this>
     */
    public function kartuRfid(): BelongsTo
    {
        return $this->belongsTo(KartuRfid::class);
    }

    /**
     * @return BelongsTo<RfidDevice, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(RfidDevice::class, 'rfid_device_id');
    }

    /**
     * @param  Builder<RfidScanLog>  $query
     * @return Builder<RfidScanLog>
     */
    public function scopeByJenis(Builder $query, string $jenis): Builder
    {
        return $query->where('jenis', $jenis);
    }

    /**
     * @return array{label: string, color: string}
     */
    public function getJenisInfoAttribute(): array
    {
        return match ($this->jenis) {
            'masuk' => ['label' => 'Masuk', 'color' => 'success'],
            'pulang' => ['label' => 'Pulang', 'color' => 'info'],
            'duplikat' => ['label' => 'Duplikat', 'color' => 'warning'],
            'ditolak' => ['label' => 'Ditolak', 'color' => 'danger'],
            'tidak_dikenal' => ['label' => 'Tidak Dikenal', 'color' => 'gray'],
            default => ['label' => $this->jenis, 'color' => 'gray'],
        };
    }
}
