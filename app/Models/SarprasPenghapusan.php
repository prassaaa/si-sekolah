<?php

namespace App\Models;

use Database\Factories\SarprasPenghapusanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SarprasPenghapusan extends Model
{
    /** @use HasFactory<SarprasPenghapusanFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'nomor',
        'sarpras_barang_id',
        'tanggal',
        'alasan',
        'jumlah',
        'nilai_sisa',
        'metode',
        'disetujui_oleh',
        'status',
        'keterangan',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'integer',
            'nilai_sisa' => 'decimal:2',
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
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    /**
     * @param  Builder<SarprasPenghapusan>  $query
     * @return Builder<SarprasPenghapusan>
     */
    public function scopeDisetujui(Builder $query): Builder
    {
        return $query->where('status', 'disetujui');
    }

    /**
     * @return array{label: string, color: string}
     */
    public function getStatusInfoAttribute(): array
    {
        return match ($this->status) {
            'diajukan' => ['label' => 'Diajukan', 'color' => 'warning'],
            'disetujui' => ['label' => 'Disetujui', 'color' => 'success'],
            'ditolak' => ['label' => 'Ditolak', 'color' => 'danger'],
            default => ['label' => $this->status, 'color' => 'gray'],
        };
    }

    /**
     * Setujui penghapusan secara idempoten: tandai status `disetujui` dan set
     * barang `dihapus` + nonaktif. Dilindungi pengecekan status dan dijalankan
     * di bawah transaksi + lock.
     */
    public function setujui(): void
    {
        DB::transaction(function (): void {
            $penghapusan = static::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($penghapusan->status === 'disetujui') {
                return;
            }

            $barang = SarprasBarang::query()
                ->lockForUpdate()
                ->findOrFail($penghapusan->sarpras_barang_id);

            $penghapusan->status = 'disetujui';
            $penghapusan->save();

            $barang->status = 'dihapus';
            $barang->is_active = false;
            $barang->save();

            $this->forceFill(['status' => 'disetujui']);
        });
    }

    protected static function booted(): void
    {
        static::creating(function (SarprasPenghapusan $penghapusan): void {
            if (empty($penghapusan->nomor)) {
                $penghapusan->nomor = static::generateNomor($penghapusan->tanggal);
            }
            if (! $penghapusan->disetujui_oleh) {
                $penghapusan->disetujui_oleh = auth()->id();
            }
        });
    }

    /**
     * Hasilkan nomor unik `PHP-YYYYMM-NNNN` di bawah transaksi + lock.
     */
    protected static function generateNomor(mixed $tanggal): string
    {
        $date = $tanggal ? Carbon::parse($tanggal) : now();
        $ym = $date->format('Ym');
        $prefix = 'PHP-'.$ym.'-';

        return DB::transaction(function () use ($prefix): string {
            $lastNomor = DB::table('sarpras_penghapusans')
                ->lockForUpdate()
                ->where('nomor', 'like', $prefix.'%')
                ->max('nomor');

            $lastNumber = $lastNomor ? (int) substr($lastNomor, -4) : 0;

            return $prefix.str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
        });
    }
}
