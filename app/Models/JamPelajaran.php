<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class JamPelajaran extends Model
{
    /** @use HasFactory<\Database\Factories\JamPelajaranFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'jam_ke',
        'waktu_mulai',
        'waktu_selesai',
        'durasi',
        'jenis',
        'keterangan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'jam_ke' => 'integer',
            'durasi' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "Jam Pelajaran ke-{$this->jam_ke} telah {$eventName}");
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeReguler(Builder $query): Builder
    {
        return $query->where('jenis', 'Reguler');
    }

    public function scopeIstirahat(Builder $query): Builder
    {
        return $query->where('jenis', 'Istirahat');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('jam_ke');
    }

    // Accessors
    public function getRentangWaktuAttribute(): string
    {
        return substr($this->waktu_mulai, 0, 5).' - '.substr($this->waktu_selesai, 0, 5);
    }

    public function getLabelAttribute(): string
    {
        return "Jam ke-{$this->jam_ke} ({$this->rentang_waktu})";
    }
}
