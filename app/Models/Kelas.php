<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Kelas extends Model
{
    /** @use HasFactory<\Database\Factories\KelasFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'kelas';

    protected $fillable = [
        'tahun_ajaran_id',
        'nama',
        'tingkat',
        'jurusan',
        'wali_kelas_id',
        'kapasitas',
        'ruangan',
        'urutan',
        'is_active',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tingkat' => 'integer',
            'kapasitas' => 'integer',
            'urutan' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "Kelas {$this->nama} telah {$eventName}");
    }

    // Relationships
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'wali_kelas_id');
    }

    public function siswas(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }

    public function jadwalPelajarans(): HasMany
    {
        return $this->hasMany(JadwalPelajaran::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeTingkat(Builder $query, int $tingkat): Builder
    {
        return $query->where('tingkat', $tingkat);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('tingkat')->orderBy('urutan')->orderBy('nama');
    }

    public function scopeTahunAktif(Builder $query): Builder
    {
        return $query->whereHas('tahunAjaran', fn ($q) => $q->where('is_active', true));
    }

    // Accessors
    public function getNamaLengkapAttribute(): string
    {
        $parts = ["Kelas {$this->nama}"];
        if ($this->jurusan) {
            $parts[] = $this->jurusan;
        }

        return implode(' - ', $parts);
    }

    public function getJumlahSiswaAttribute(): int
    {
        return $this->siswas()->count();
    }
}
