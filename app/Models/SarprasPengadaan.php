<?php

namespace App\Models;

use App\Services\Sarpras\SarprasJournalPoster;
use Database\Factories\SarprasPengadaanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SarprasPengadaan extends Model
{
    /** @use HasFactory<SarprasPengadaanFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected const MONEY_SCALE = 2;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor',
        'tanggal',
        'sumber_dana',
        'penyedia',
        'total_biaya',
        'status',
        'keterangan',
        'dibuat_oleh',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_biaya' => 'decimal:2',
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
     * @return HasMany<SarprasPengadaanItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SarprasPengadaanItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * @param  Builder<SarprasPengadaan>  $query
     * @return Builder<SarprasPengadaan>
     */
    public function scopeDiterima(Builder $query): Builder
    {
        return $query->where('status', 'diterima');
    }

    /**
     * @return array{label: string, color: string}
     */
    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'draft' => ['label' => 'Draft', 'color' => 'gray'],
            'disetujui' => ['label' => 'Disetujui', 'color' => 'info'],
            'diterima' => ['label' => 'Diterima', 'color' => 'success'],
            'batal' => ['label' => 'Batal', 'color' => 'danger'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }

    /**
     * Hitung ulang `total_biaya` dari subtotal seluruh item (bc-math),
     * lalu simpan di bawah transaksi + lock.
     */
    public function recalculateTotal(): void
    {
        DB::transaction(function (): void {
            $pengadaan = static::query()->lockForUpdate()->findOrFail($this->getKey());

            $total = '0';
            foreach ($pengadaan->items()->get() as $item) {
                $total = bcadd($total, (string) $item->subtotal, self::MONEY_SCALE);
            }

            $pengadaan->total_biaya = $total;
            $pengadaan->save();

            $this->forceFill(['total_biaya' => $total]);
        });
    }

    /**
     * Terima pengadaan secara idempoten: untuk tiap item, buat/menambah stok
     * `SarprasBarang`, lalu tandai pengadaan `diterima`. Dilindungi pengecekan
     * status agar tidak terjadi intake ganda. Semua di bawah transaksi + lock.
     */
    public function terima(): void
    {
        DB::transaction(function (): void {
            $pengadaan = static::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($pengadaan->status === 'diterima') {
                return;
            }

            foreach ($pengadaan->items()->get() as $item) {
                $kodeInventaris = 'INV-'.$pengadaan->nomor.'-'.$item->getKey();

                $barang = SarprasBarang::query()
                    ->lockForUpdate()
                    ->where('kode_inventaris', $kodeInventaris)
                    ->first();

                if ($barang) {
                    $barang->jumlah = $barang->jumlah + $item->jumlah;
                    $barang->save();

                    continue;
                }

                SarprasBarang::query()->create([
                    'kode_inventaris' => $kodeInventaris,
                    'nama' => $item->nama_barang,
                    'sarpras_kategori_id' => $item->sarpras_kategori_id,
                    'tipe' => 'bahan',
                    'kondisi' => 'baik',
                    'status' => 'tersedia',
                    'sumber_dana' => $pengadaan->sumber_dana,
                    'tahun_perolehan' => $pengadaan->tanggal?->year,
                    'harga_perolehan' => $item->harga_satuan,
                    'jumlah' => $item->jumlah,
                    'satuan' => $item->satuan,
                ]);
            }

            $pengadaan->status = 'diterima';
            $pengadaan->save();

            $this->forceFill(['status' => 'diterima']);

            app(SarprasJournalPoster::class)->postPengadaan($pengadaan);
        });
    }

    /**
     * Reverse the procurement journal entry. Use when a received pengadaan is
     * cancelled. Safe no-op when no journal was posted.
     */
    public function reverseJurnal(): void
    {
        app(SarprasJournalPoster::class)->reversePengadaan($this);
    }

    protected static function booted(): void
    {
        static::creating(function (SarprasPengadaan $pengadaan): void {
            if (empty($pengadaan->nomor)) {
                $pengadaan->nomor = static::generateNomor($pengadaan->tanggal);
            }
            if (! $pengadaan->dibuat_oleh) {
                $pengadaan->dibuat_oleh = auth()->id();
            }
        });
    }

    /**
     * Hasilkan nomor unik `PGD-YYYYMM-NNNN` di bawah transaksi + lock.
     */
    protected static function generateNomor(mixed $tanggal): string
    {
        $date = $tanggal ? Carbon::parse($tanggal) : now();
        $ym = $date->format('Ym');
        $prefix = 'PGD-'.$ym.'-';

        return DB::transaction(function () use ($prefix): string {
            $lastNomor = DB::table('sarpras_pengadaans')
                ->lockForUpdate()
                ->where('nomor', 'like', $prefix.'%')
                ->max('nomor');

            $lastNumber = $lastNomor ? (int) substr($lastNomor, -4) : 0;

            return $prefix.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        });
    }
}
