<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Tahfidz extends Model
{
    /** @use HasFactory<\Database\Factories\TahfidzFactory> */
    use HasFactory;

    use LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'siswa_id',
        'semester_id',
        'penguji_id',
        'surah',
        'ayat_mulai',
        'ayat_selesai',
        'jumlah_ayat',
        'juz',
        'tanggal',
        'jenis',
        'status',
        'nilai',
        'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'ayat_mulai' => 'integer',
            'ayat_selesai' => 'integer',
            'jumlah_ayat' => 'integer',
            'juz' => 'integer',
            'nilai' => 'integer',
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
     * @return BelongsTo<Siswa, Tahfidz>
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * @return BelongsTo<Semester, Tahfidz>
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * @return BelongsTo<Pegawai, Tahfidz>
     */
    public function penguji(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'penguji_id');
    }

    // =====================
    // SCOPES
    // =====================

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Tahfidz>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Tahfidz>
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Tahfidz>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Tahfidz>
     */
    public function scopeLulus($query)
    {
        return $query->where('status', 'lulus');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Tahfidz>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Tahfidz>
     */
    public function scopeJenis($query, string $jenis)
    {
        return $query->where('jenis', $jenis);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Tahfidz>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Tahfidz>
     */
    public function scopeForSiswa($query, int $siswaId)
    {
        return $query->where('siswa_id', $siswaId);
    }

    // =====================
    // ACCESSORS
    // =====================

    /**
     * Format: Surah Ayat X - Y
     */
    public function getHafalanAttribute(): string
    {
        return sprintf('%s: %d - %d', $this->surah, $this->ayat_mulai, $this->ayat_selesai);
    }

    /**
     * Status label dengan warna
     *
     * @return array{label: string, color: string}
     */
    public function getStatusInfoAttribute(): array
    {
        $statusMap = [
            'lulus' => ['label' => 'Lulus', 'color' => 'success'],
            'mengulang' => ['label' => 'Mengulang', 'color' => 'danger'],
            'pending' => ['label' => 'Pending', 'color' => 'warning'],
        ];

        return $statusMap[$this->status] ?? ['label' => $this->status, 'color' => 'gray'];
    }

    /**
     * Jenis label
     *
     * @return array{label: string, color: string}
     */
    public function getJenisInfoAttribute(): array
    {
        $jenisMap = [
            'setoran' => ['label' => 'Setoran', 'color' => 'info'],
            'murojaah' => ['label' => 'Murojaah', 'color' => 'warning'],
            'ujian' => ['label' => 'Ujian', 'color' => 'success'],
        ];

        return $jenisMap[$this->jenis] ?? ['label' => $this->jenis, 'color' => 'gray'];
    }

    /**
     * List surah untuk dropdown
     *
     * @return array<string, string>
     */
    public static function surahOptions(): array
    {
        return [
            'Al-Fatihah' => 'Al-Fatihah (1)',
            'Al-Baqarah' => 'Al-Baqarah (2)',
            'Ali Imran' => 'Ali Imran (3)',
            'An-Nisa' => 'An-Nisa (4)',
            'Al-Maidah' => 'Al-Maidah (5)',
            'Al-An\'am' => 'Al-An\'am (6)',
            'Al-A\'raf' => 'Al-A\'raf (7)',
            'Al-Anfal' => 'Al-Anfal (8)',
            'At-Taubah' => 'At-Taubah (9)',
            'Yunus' => 'Yunus (10)',
            'Hud' => 'Hud (11)',
            'Yusuf' => 'Yusuf (12)',
            'Ar-Ra\'d' => 'Ar-Ra\'d (13)',
            'Ibrahim' => 'Ibrahim (14)',
            'Al-Hijr' => 'Al-Hijr (15)',
            'An-Nahl' => 'An-Nahl (16)',
            'Al-Isra' => 'Al-Isra (17)',
            'Al-Kahf' => 'Al-Kahf (18)',
            'Maryam' => 'Maryam (19)',
            'Taha' => 'Taha (20)',
            'Al-Anbiya' => 'Al-Anbiya (21)',
            'Al-Hajj' => 'Al-Hajj (22)',
            'Al-Mu\'minun' => 'Al-Mu\'minun (23)',
            'An-Nur' => 'An-Nur (24)',
            'Al-Furqan' => 'Al-Furqan (25)',
            'Asy-Syu\'ara' => 'Asy-Syu\'ara (26)',
            'An-Naml' => 'An-Naml (27)',
            'Al-Qasas' => 'Al-Qasas (28)',
            'Al-Ankabut' => 'Al-Ankabut (29)',
            'Ar-Rum' => 'Ar-Rum (30)',
            'Luqman' => 'Luqman (31)',
            'As-Sajdah' => 'As-Sajdah (32)',
            'Al-Ahzab' => 'Al-Ahzab (33)',
            'Saba' => 'Saba (34)',
            'Fatir' => 'Fatir (35)',
            'Yasin' => 'Yasin (36)',
            'As-Saffat' => 'As-Saffat (37)',
            'Sad' => 'Sad (38)',
            'Az-Zumar' => 'Az-Zumar (39)',
            'Ghafir' => 'Ghafir (40)',
            'Fussilat' => 'Fussilat (41)',
            'Asy-Syura' => 'Asy-Syura (42)',
            'Az-Zukhruf' => 'Az-Zukhruf (43)',
            'Ad-Dukhan' => 'Ad-Dukhan (44)',
            'Al-Jasiyah' => 'Al-Jasiyah (45)',
            'Al-Ahqaf' => 'Al-Ahqaf (46)',
            'Muhammad' => 'Muhammad (47)',
            'Al-Fath' => 'Al-Fath (48)',
            'Al-Hujurat' => 'Al-Hujurat (49)',
            'Qaf' => 'Qaf (50)',
            'Adz-Dzariyat' => 'Adz-Dzariyat (51)',
            'At-Tur' => 'At-Tur (52)',
            'An-Najm' => 'An-Najm (53)',
            'Al-Qamar' => 'Al-Qamar (54)',
            'Ar-Rahman' => 'Ar-Rahman (55)',
            'Al-Waqi\'ah' => 'Al-Waqi\'ah (56)',
            'Al-Hadid' => 'Al-Hadid (57)',
            'Al-Mujadilah' => 'Al-Mujadilah (58)',
            'Al-Hasyr' => 'Al-Hasyr (59)',
            'Al-Mumtahanah' => 'Al-Mumtahanah (60)',
            'As-Saff' => 'As-Saff (61)',
            'Al-Jumu\'ah' => 'Al-Jumu\'ah (62)',
            'Al-Munafiqun' => 'Al-Munafiqun (63)',
            'At-Taghabun' => 'At-Taghabun (64)',
            'At-Talaq' => 'At-Talaq (65)',
            'At-Tahrim' => 'At-Tahrim (66)',
            'Al-Mulk' => 'Al-Mulk (67)',
            'Al-Qalam' => 'Al-Qalam (68)',
            'Al-Haqqah' => 'Al-Haqqah (69)',
            'Al-Ma\'arij' => 'Al-Ma\'arij (70)',
            'Nuh' => 'Nuh (71)',
            'Al-Jinn' => 'Al-Jinn (72)',
            'Al-Muzzammil' => 'Al-Muzzammil (73)',
            'Al-Muddassir' => 'Al-Muddassir (74)',
            'Al-Qiyamah' => 'Al-Qiyamah (75)',
            'Al-Insan' => 'Al-Insan (76)',
            'Al-Mursalat' => 'Al-Mursalat (77)',
            'An-Naba\'' => 'An-Naba\' (78)',
            'An-Nazi\'at' => 'An-Nazi\'at (79)',
            'Abasa' => 'Abasa (80)',
            'At-Takwir' => 'At-Takwir (81)',
            'Al-Infitar' => 'Al-Infitar (82)',
            'Al-Mutaffifin' => 'Al-Mutaffifin (83)',
            'Al-Insyiqaq' => 'Al-Insyiqaq (84)',
            'Al-Buruj' => 'Al-Buruj (85)',
            'At-Tariq' => 'At-Tariq (86)',
            'Al-A\'la' => 'Al-A\'la (87)',
            'Al-Gasyiyah' => 'Al-Gasyiyah (88)',
            'Al-Fajr' => 'Al-Fajr (89)',
            'Al-Balad' => 'Al-Balad (90)',
            'Asy-Syams' => 'Asy-Syams (91)',
            'Al-Lail' => 'Al-Lail (92)',
            'Ad-Duha' => 'Ad-Duha (93)',
            'Asy-Syarh' => 'Asy-Syarh (94)',
            'At-Tin' => 'At-Tin (95)',
            'Al-Alaq' => 'Al-Alaq (96)',
            'Al-Qadr' => 'Al-Qadr (97)',
            'Al-Bayyinah' => 'Al-Bayyinah (98)',
            'Az-Zalzalah' => 'Az-Zalzalah (99)',
            'Al-Adiyat' => 'Al-Adiyat (100)',
            'Al-Qari\'ah' => 'Al-Qari\'ah (101)',
            'At-Takasur' => 'At-Takasur (102)',
            'Al-Asr' => 'Al-Asr (103)',
            'Al-Humazah' => 'Al-Humazah (104)',
            'Al-Fil' => 'Al-Fil (105)',
            'Quraisy' => 'Quraisy (106)',
            'Al-Ma\'un' => 'Al-Ma\'un (107)',
            'Al-Kausar' => 'Al-Kausar (108)',
            'Al-Kafirun' => 'Al-Kafirun (109)',
            'An-Nasr' => 'An-Nasr (110)',
            'Al-Lahab' => 'Al-Lahab (111)',
            'Al-Ikhlas' => 'Al-Ikhlas (112)',
            'Al-Falaq' => 'Al-Falaq (113)',
            'An-Nas' => 'An-Nas (114)',
        ];
    }
}
