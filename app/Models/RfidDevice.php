<?php

namespace App\Models;

use Database\Factories\RfidDeviceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RfidDevice extends Model
{
    /** @use HasFactory<RfidDeviceFactory> */
    use HasFactory, LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'kode',
        'jenis',
        'lokasi',
        'api_token',
        'token_prefix',
        'terakhir_aktif',
        'is_active',
        'keterangan',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'api_token',
        'token_prefix',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'terakhir_aktif' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'kode', 'jenis', 'lokasi', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return HasMany<RfidScanLog, $this>
     */
    public function scanLogs(): HasMany
    {
        return $this->hasMany(RfidScanLog::class);
    }

    /**
     * @param  Builder<RfidDevice>  $query
     * @return Builder<RfidDevice>
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function generateToken(): string
    {
        $plain = Str::random(60);
        $this->api_token = Hash::make($plain);
        $this->token_prefix = substr($plain, 0, 8);
        $this->save();

        return $plain;
    }

    public function verifyToken(string $plain): bool
    {
        return Hash::check($plain, $this->api_token);
    }

    public function tandaiAktif(): void
    {
        $this->forceFill(['terakhir_aktif' => now()])->save();
    }

    /**
     * @return array{label: string, color: string}
     */
    public function getJenisInfoAttribute(): array
    {
        return match ($this->jenis) {
            'gerbang_masuk' => ['label' => 'Gerbang Masuk', 'color' => 'success'],
            'gerbang_pulang' => ['label' => 'Gerbang Pulang', 'color' => 'info'],
            'serbaguna' => ['label' => 'Serbaguna', 'color' => 'gray'],
            default => ['label' => $this->jenis, 'color' => 'gray'],
        };
    }
}
