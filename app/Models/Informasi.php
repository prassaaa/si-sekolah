<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Informasi extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'informasis';

    protected $fillable = [
        'judul',
        'slug',
        'kategori',
        'ringkasan',
        'konten',
        'gambar',
        'prioritas',
        'tanggal_publish',
        'tanggal_expired',
        'is_published',
        'is_pinned',
        'created_by',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_publish' => 'date',
            'tanggal_expired' => 'date',
            'is_published' => 'boolean',
            'is_pinned' => 'boolean',
            'views_count' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['judul', 'kategori', 'is_published', 'is_pinned'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::creating(function ($informasi) {
            if (empty($informasi->slug)) {
                $informasi->slug = static::generateUniqueSlug(
                    $informasi->judul,
                );
            }
        });

        static::updating(function ($informasi) {
            if ($informasi->isDirty('judul') && ! $informasi->isDirty('slug')) {
                $informasi->slug = static::generateUniqueSlug(
                    $informasi->judul,
                    $informasi->id,
                );
            }
        });
    }

    private static function generateUniqueSlug(
        string $judul,
        ?int $excludeId = null,
    ): string {
        $slug = Str::slug($judul);
        $original = $slug;
        $counter = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $original.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function scopePublished($query)
    {
        return $query
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('tanggal_publish')->orWhere(
                    'tanggal_publish',
                    '<=',
                    now(),
                );
            })
            ->where(function ($q) {
                $q->whereNull('tanggal_expired')->orWhere(
                    'tanggal_expired',
                    '>=',
                    now(),
                );
            });
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }
}
