<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Pegawai extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'pegawais';

    protected $fillable = [
        'nip',
        'nuptk',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'alamat',
        'telepon',
        'email',
        'foto',
        'jabatan_id',
        'user_id',
        'status_kepegawaian',
        'pendidikan_terakhir',
        'jurusan',
        'universitas',
        'tahun_lulus',
        'tanggal_masuk',
        'tanggal_keluar',
        'no_rekening',
        'nama_bank',
        'npwp',
        'no_bpjs_kesehatan',
        'no_bpjs_ketenagakerjaan',
        'status_pernikahan',
        'jumlah_tanggungan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
            'tahun_lulus' => 'integer',
            'jumlah_tanggungan' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nip', 'nama', 'jabatan_id', 'status_kepegawaian', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(JabatanPegawai::class, 'jabatan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function presensiHarianPegawais(): HasMany
    {
        return $this->hasMany(PresensiHarianPegawai::class);
    }

    public function kartuRfids(): MorphMany
    {
        return $this->morphMany(KartuRfid::class, 'owner');
    }

    /**
     * @return MorphOne<KartuRfid, $this>
     */
    public function kartuRfidAktif(): MorphOne
    {
        return $this->morphOne(KartuRfid::class, 'owner')->where('status', 'aktif');
    }

    /**
     * Alias untuk nama — digunakan pada relasi dot-notation Filament (pegawai.nama_lengkap).
     * Mengembalikan nama pegawai secara langsung; gunakan field `nama` jika mengakses model langsung.
     */
    public function getNamaLengkapAttribute(): string
    {
        return $this->nama;
    }

    public function getUmurAttribute(): ?int
    {
        if (! $this->tanggal_lahir) {
            return null;
        }

        return $this->tanggal_lahir->age;
    }

    public function getMasaKerjaAttribute(): ?string
    {
        if (! $this->tanggal_masuk) {
            return null;
        }

        // Use tanggal_keluar as end point for resigned staff; fall back to today for active staff.
        $end = $this->tanggal_keluar ?? now();
        $diff = $this->tanggal_masuk->diff($end);

        return $diff->y.' tahun '.$diff->m.' bulan';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGuru($query)
    {
        return $query->whereHas('jabatan', function ($q) {
            $q->where('jenis', 'Fungsional');
        });
    }

    public function scopeStaff($query)
    {
        return $query->whereHas('jabatan', function ($q) {
            $q->whereIn('jenis', ['Struktural', 'Non-Fungsional']);
        });
    }
}
