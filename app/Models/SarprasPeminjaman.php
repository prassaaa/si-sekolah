<?php

namespace App\Models;

use Database\Factories\SarprasPeminjamanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SarprasPeminjaman extends Model
{
    /** @use HasFactory<SarprasPeminjamanFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'sarpras_peminjamans';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor',
        'sarpras_barang_id',
        'peminjam_type',
        'peminjam_id',
        'jumlah',
        'tanggal_pinjam',
        'tanggal_harus_kembali',
        'tanggal_kembali',
        'kondisi_pinjam',
        'kondisi_kembali',
        'status',
        'petugas_id',
        'catatan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'tanggal_pinjam' => 'date',
            'tanggal_harus_kembali' => 'date',
            'tanggal_kembali' => 'date',
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
     * @return MorphTo<Model, $this>
     */
    public function peminjam(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Pegawai, $this>
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class, 'petugas_id');
    }

    /**
     * @param  Builder<SarprasPeminjaman>  $query
     * @return Builder<SarprasPeminjaman>
     */
    public function scopeDipinjam(Builder $query): Builder
    {
        return $query->where('status', 'dipinjam');
    }

    /**
     * @param  Builder<SarprasPeminjaman>  $query
     * @return Builder<SarprasPeminjaman>
     */
    public function scopeTerlambat(Builder $query): Builder
    {
        return $query->where('status', 'terlambat');
    }

    /**
     * @return array{label: string, color: string}
     */
    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'dipinjam' => ['label' => 'Dipinjam', 'color' => 'warning'],
            'dikembalikan' => ['label' => 'Dikembalikan', 'color' => 'success'],
            'terlambat' => ['label' => 'Terlambat', 'color' => 'danger'],
            'hilang' => ['label' => 'Hilang', 'color' => 'danger'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }

    /**
     * Catat pengembalian barang: simpan kondisi & tanggal kembali, lalu
     * pulihkan ketersediaan barang (status/stok) di bawah transaksi + lock.
     */
    public function kembalikan(string $kondisiKembali): void
    {
        DB::transaction(function () use ($kondisiKembali): void {
            $peminjaman = static::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($peminjaman->status !== 'dipinjam' && $peminjaman->status !== 'terlambat') {
                return;
            }

            $barang = SarprasBarang::query()
                ->lockForUpdate()
                ->findOrFail($peminjaman->sarpras_barang_id);

            $tanggalKembali = now();
            $status = $tanggalKembali->startOfDay()->greaterThan($peminjaman->tanggal_harus_kembali)
                ? 'terlambat'
                : 'dikembalikan';

            $peminjaman->forceFill([
                'tanggal_kembali' => $tanggalKembali,
                'kondisi_kembali' => $kondisiKembali,
                'status' => $status,
            ])->save();

            if ($barang->isBahan()) {
                $barang->jumlah = $barang->jumlah + $peminjaman->jumlah;
            } else {
                $barang->status = 'tersedia';
            }

            $barang->kondisi = $kondisiKembali;
            $barang->save();

            $this->forceFill($peminjaman->getAttributes());
        });
    }

    protected static function booted(): void
    {
        static::creating(function (SarprasPeminjaman $peminjaman): void {
            if (empty($peminjaman->nomor)) {
                $peminjaman->nomor = static::generateNomor($peminjaman->tanggal_pinjam);
            }
        });

        static::created(function (SarprasPeminjaman $peminjaman): void {
            if ($peminjaman->status !== 'dipinjam') {
                return;
            }

            DB::transaction(function () use ($peminjaman): void {
                $barang = SarprasBarang::query()
                    ->lockForUpdate()
                    ->findOrFail($peminjaman->sarpras_barang_id);

                if ($barang->isBahan()) {
                    if ($barang->jumlah < $peminjaman->jumlah) {
                        throw ValidationException::withMessages([
                            'sarpras_barang_id' => "Stok {$barang->nama} tidak mencukupi (tersedia {$barang->jumlah}).",
                        ]);
                    }

                    $barang->jumlah = $barang->jumlah - $peminjaman->jumlah;
                    $barang->save();
                } else {
                    if ($barang->status !== 'tersedia') {
                        throw ValidationException::withMessages([
                            'sarpras_barang_id' => "Barang {$barang->nama} tidak tersedia untuk dipinjam.",
                        ]);
                    }

                    $barang->status = 'dipinjam';
                    $barang->save();
                }
            });
        });
    }

    /**
     * Hasilkan nomor unik `PJM-YYYYMM-NNNN` di bawah transaksi + lock untuk
     * mencegah tabrakan unique akibat race condition (pelajaran SlipGaji H17).
     */
    protected static function generateNomor(mixed $tanggal): string
    {
        $date = $tanggal ? Carbon::parse($tanggal) : now();
        $ym = $date->format('Ym');
        $prefix = 'PJM-'.$ym.'-';

        return DB::transaction(function () use ($prefix): string {
            $lastNomor = DB::table('sarpras_peminjamans')
                ->lockForUpdate()
                ->where('nomor', 'like', $prefix.'%')
                ->max('nomor');

            $lastNumber = $lastNomor ? (int) substr($lastNomor, -4) : 0;

            return $prefix.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        });
    }
}
