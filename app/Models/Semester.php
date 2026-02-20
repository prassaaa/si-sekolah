<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Semester extends Model
{
    /** @use HasFactory<\Database\Factories\SemesterFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'tahun_ajaran_id',
        'semester',
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_active',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(
                fn (
                    string $eventName,
                ): string => "Semester {$this->nama} telah {$eventName}",
            );
    }

    // Relationships
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeGanjil(Builder $query): Builder
    {
        return $query->where('semester', 1);
    }

    public function scopeGenap(Builder $query): Builder
    {
        return $query->where('semester', 2);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('tanggal_mulai');
    }

    // Helpers
    public static function getActive(): ?self
    {
        return self::active()->first();
    }

    public function activate(): void
    {
        DB::transaction(function () {
            self::where('id', '!=', $this->id)->update(['is_active' => false]);
            $this->update(['is_active' => true]);
        });
    }

    public function getSemesterLabelAttribute(): string
    {
        return $this->semester === 1 ? 'Ganjil' : 'Genap';
    }
}
