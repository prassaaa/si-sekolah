<?php

namespace App\Models;

use App\Services\Sarpras\SarprasJournalPoster;
use Database\Factories\SarprasPemeliharaanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SarprasPemeliharaan extends Model
{
    /** @use HasFactory<SarprasPemeliharaanFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor',
        'sarpras_barang_id',
        'jenis',
        'tanggal',
        'tanggal_selesai',
        'deskripsi_masalah',
        'tindakan',
        'pelaksana',
        'nama_vendor',
        'biaya',
        'kondisi_sebelum',
        'kondisi_sesudah',
        'status',
        'dicatat_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tanggal_selesai' => 'date',
            'biaya' => 'decimal:2',
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
     * @return BelongsTo<SarprasBarang, $this>
     */
    public function barang(): BelongsTo
    {
        return $this->belongsTo(SarprasBarang::class, 'sarpras_barang_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    /**
     * @param  Builder<SarprasPemeliharaan>  $query
     * @return Builder<SarprasPemeliharaan>
     */
    public function scopeProses(Builder $query): Builder
    {
        return $query->where('status', 'proses');
    }

    /**
     * @return array{label: string, color: string}
     */
    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'dijadwalkan' => ['label' => 'Dijadwalkan', 'color' => 'gray'],
            'proses' => ['label' => 'Proses', 'color' => 'warning'],
            'selesai' => ['label' => 'Selesai', 'color' => 'success'],
            'batal' => ['label' => 'Batal', 'color' => 'danger'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }

    protected static function booted(): void
    {
        static::creating(function (SarprasPemeliharaan $pemeliharaan): void {
            if (empty($pemeliharaan->nomor)) {
                $pemeliharaan->nomor = static::generateNomor($pemeliharaan->tanggal);
            }
            if (! $pemeliharaan->dicatat_oleh) {
                $pemeliharaan->dicatat_oleh = auth()->id();
            }
        });

        static::saved(function (SarprasPemeliharaan $pemeliharaan): void {
            $relevan = $pemeliharaan->wasChanged('status')
                || $pemeliharaan->wasChanged('biaya')
                || $pemeliharaan->wasRecentlyCreated;

            if (! $relevan) {
                return;
            }

            static::syncBarangStatus($pemeliharaan);

            if ($pemeliharaan->status === 'selesai') {
                app(SarprasJournalPoster::class)->postPemeliharaan($pemeliharaan);
            } else {
                app(SarprasJournalPoster::class)->reversePemeliharaan($pemeliharaan);
            }
        });

        static::deleted(function (SarprasPemeliharaan $pemeliharaan): void {
            app(SarprasJournalPoster::class)->reversePemeliharaan($pemeliharaan);
        });
    }

    /**
     * Selaraskan status barang dengan status pemeliharaan: `proses` membuat
     * barang `perbaikan`, `selesai` mengembalikan ke `tersedia` dan menyimpan
     * `kondisi_sesudah` ke barang. Dijalankan di bawah transaksi + lock.
     */
    protected static function syncBarangStatus(SarprasPemeliharaan $pemeliharaan): void
    {
        if (! in_array($pemeliharaan->status, ['proses', 'selesai'], true)) {
            return;
        }

        DB::transaction(function () use ($pemeliharaan): void {
            $barang = SarprasBarang::query()
                ->lockForUpdate()
                ->findOrFail($pemeliharaan->sarpras_barang_id);

            if ($pemeliharaan->status === 'proses') {
                $barang->status = 'perbaikan';
            } else {
                $barang->status = 'tersedia';
                if ($pemeliharaan->kondisi_sesudah) {
                    $barang->kondisi = $pemeliharaan->kondisi_sesudah;
                }
            }

            $barang->save();
        });
    }

    /**
     * Hasilkan nomor unik `PML-YYYYMM-NNNN` di bawah transaksi + lock.
     */
    protected static function generateNomor(mixed $tanggal): string
    {
        $date = $tanggal ? Carbon::parse($tanggal) : now();
        $ym = $date->format('Ym');
        $prefix = 'PML-'.$ym.'-';

        return DB::transaction(function () use ($prefix): string {
            $lastNomor = DB::table('sarpras_pemeliharaans')
                ->lockForUpdate()
                ->where('nomor', 'like', $prefix.'%')
                ->max('nomor');

            $lastNumber = $lastNomor ? (int) substr($lastNomor, -4) : 0;

            return $prefix.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        });
    }
}
