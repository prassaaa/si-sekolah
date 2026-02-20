<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Siswa extends Model
{
    /** @use HasFactory<\Database\Factories\SiswaFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        // Identitas Utama
        'nis',
        'nisn',
        'nama',
        'nama_panggilan',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'nik',
        'no_kk',
        'no_akta',
        'agama',
        'kewarganegaraan',
        'anak_ke',
        'jumlah_saudara',
        // Alamat
        'alamat',
        'rt',
        'rw',
        'kelurahan',
        'kecamatan',
        'kota',
        'provinsi',
        'kode_pos',
        // Kontak
        'telepon',
        'hp',
        'email',
        // Data Akademik
        'kelas_id',
        'tanggal_masuk',
        'asal_sekolah',
        'status',
        'tahun_masuk',
        // Data Kesehatan
        'golongan_darah',
        'tinggi_badan',
        'berat_badan',
        'riwayat_penyakit',
        // Data Ayah
        'nama_ayah',
        'nik_ayah',
        'tempat_lahir_ayah',
        'tanggal_lahir_ayah',
        'pendidikan_ayah',
        'pekerjaan_ayah',
        'penghasilan_ayah',
        'telepon_ayah',
        'alamat_ayah',
        // Data Ibu
        'nama_ibu',
        'nik_ibu',
        'tempat_lahir_ibu',
        'tanggal_lahir_ibu',
        'pendidikan_ibu',
        'pekerjaan_ibu',
        'penghasilan_ibu',
        'telepon_ibu',
        'alamat_ibu',
        // Data Wali
        'nama_wali',
        'nik_wali',
        'hubungan_wali',
        'tempat_lahir_wali',
        'tanggal_lahir_wali',
        'pendidikan_wali',
        'pekerjaan_wali',
        'penghasilan_wali',
        'telepon_wali',
        'alamat_wali',
        // Dokumen
        'foto',
        'foto_kk',
        'foto_akta',
        'foto_ijazah',
        // Keterangan
        'catatan',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
            'tanggal_lahir_ayah' => 'date',
            'tanggal_lahir_ibu' => 'date',
            'tanggal_lahir_wali' => 'date',
            'penghasilan_ayah' => 'decimal:2',
            'penghasilan_ibu' => 'decimal:2',
            'penghasilan_wali' => 'decimal:2',
            'tinggi_badan' => 'decimal:2',
            'berat_badan' => 'decimal:2',
            'anak_ke' => 'integer',
            'jumlah_saudara' => 'integer',
            'tahun_masuk' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // =====================
    // RELATIONSHIPS
    // =====================

    /**
     * @return BelongsTo<Kelas, Siswa>
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tagihanSiswas(): HasMany
    {
        return $this->hasMany(TagihanSiswa::class);
    }

    public function pembayarans(): HasManyThrough
    {
        return $this->hasManyThrough(Pembayaran::class, TagihanSiswa::class);
    }

    public function pelanggarans(): HasMany
    {
        return $this->hasMany(Pelanggaran::class);
    }

    public function prestasis(): HasMany
    {
        return $this->hasMany(Prestasi::class);
    }

    public function konselings(): HasMany
    {
        return $this->hasMany(Konseling::class);
    }

    public function tahfidzs(): HasMany
    {
        return $this->hasMany(Tahfidz::class);
    }

    public function izinKeluars(): HasMany
    {
        return $this->hasMany(IzinKeluar::class);
    }

    public function izinPulangs(): HasMany
    {
        return $this->hasMany(IzinPulang::class);
    }

    public function tabunganSiswas(): HasMany
    {
        return $this->hasMany(TabunganSiswa::class);
    }

    public function buktiTransfers(): HasMany
    {
        return $this->hasMany(BuktiTransfer::class);
    }

    public function kelulusans(): HasMany
    {
        return $this->hasMany(Kelulusan::class);
    }

    public function kenaikanKelas(): HasMany
    {
        return $this->hasMany(KenaikanKelas::class);
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Siswa>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Siswa>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Siswa>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Siswa>
     */
    public function scopeStatusAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Siswa>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Siswa>
     */
    public function scopeLakiLaki($query)
    {
        return $query->where('jenis_kelamin', 'L');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Siswa>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Siswa>
     */
    public function scopePerempuan($query)
    {
        return $query->where('jenis_kelamin', 'P');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Siswa>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Siswa>
     */
    public function scopeKelas($query, int $kelasId)
    {
        return $query->where('kelas_id', $kelasId);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Siswa>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Siswa>
     */
    public function scopeTahunMasuk($query, int $tahun)
    {
        return $query->where('tahun_masuk', $tahun);
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Format: NIS - Nama
     */
    public function getNamaLengkapAttribute(): string
    {
        return $this->nis.' - '.$this->nama;
    }

    /**
     * Label jenis kelamin
     */
    public function getJenisKelaminLabelAttribute(): string
    {
        return $this->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
    }

    /**
     * Tempat, tanggal lahir
     */
    public function getTtlAttribute(): string
    {
        if (! $this->tempat_lahir || ! $this->tanggal_lahir) {
            return '-';
        }

        return $this->tempat_lahir.', '.$this->tanggal_lahir->format('d F Y');
    }

    /**
     * Hitung usia
     */
    public function getUsiaAttribute(): ?int
    {
        if (! $this->tanggal_lahir) {
            return null;
        }

        return $this->tanggal_lahir->age;
    }

    /**
     * Alamat lengkap
     */
    public function getAlamatLengkapAttribute(): string
    {
        $parts = array_filter([
            $this->alamat,
            $this->rt ? 'RT '.$this->rt : null,
            $this->rw ? 'RW '.$this->rw : null,
            $this->kelurahan,
            $this->kecamatan,
            $this->kota,
            $this->provinsi,
            $this->kode_pos,
        ]);

        return implode(', ', $parts) ?: '-';
    }

    /**
     * Status label dengan warna
     *
     * @return array{label: string, color: string}
     */
    public function getStatusInfoAttribute(): array
    {
        $statusMap = [
            'aktif' => ['label' => 'Aktif', 'color' => 'success'],
            'lulus' => ['label' => 'Lulus', 'color' => 'info'],
            'pindah' => ['label' => 'Pindah', 'color' => 'warning'],
            'dikeluarkan' => ['label' => 'Dikeluarkan', 'color' => 'danger'],
            'dropout' => ['label' => 'Dropout', 'color' => 'danger'],
            'tidak_aktif' => ['label' => 'Tidak Aktif', 'color' => 'gray'],
        ];

        return $statusMap[$this->status] ?? ['label' => $this->status, 'color' => 'gray'];
    }
}
