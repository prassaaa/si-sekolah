<?php

namespace App\Models;

use Database\Factories\SarprasPengadaanItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SarprasPengadaanItem extends Model
{
    /** @use HasFactory<SarprasPengadaanItemFactory> */
    use HasFactory;

    protected const MONEY_SCALE = 2;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sarpras_pengadaan_id',
        'nama_barang',
        'sarpras_kategori_id',
        'jumlah',
        'satuan',
        'harga_satuan',
        'subtotal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<SarprasPengadaan, $this>
     */
    public function pengadaan(): BelongsTo
    {
        return $this->belongsTo(SarprasPengadaan::class, 'sarpras_pengadaan_id');
    }

    /**
     * @return BelongsTo<SarprasKategori, $this>
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(SarprasKategori::class, 'sarpras_kategori_id');
    }

    protected static function booted(): void
    {
        static::saving(function (SarprasPengadaanItem $item): void {
            $item->subtotal = bcmul((string) $item->jumlah, (string) $item->harga_satuan, self::MONEY_SCALE);
        });

        static::saved(function (SarprasPengadaanItem $item): void {
            $item->pengadaan?->recalculateTotal();
        });

        static::deleted(function (SarprasPengadaanItem $item): void {
            $item->pengadaan?->recalculateTotal();
        });
    }
}
