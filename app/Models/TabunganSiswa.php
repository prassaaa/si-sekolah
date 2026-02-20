<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TabunganSiswa extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'siswa_id',
        'jenis',
        'nominal',
        'saldo',
        'tanggal',
        'keterangan',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'saldo' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'siswa_id',
                'jenis',
                'nominal',
                'saldo',
                'tanggal',
                'keterangan',
            ])
            ->logOnlyDirty()
            ->useLogName('tabungan_siswa');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getSaldoSiswa(int $siswaId): float
    {
        return static::where('siswa_id', $siswaId)
            ->selectRaw(
                "SUM(CASE WHEN jenis = 'setor' THEN nominal ELSE -nominal END) as saldo",
            )
            ->value('saldo') ?? 0;
    }

    protected static function booted(): void
    {
        static::creating(function (TabunganSiswa $tabungan) {
            if (! $tabungan->user_id) {
                $tabungan->user_id = auth()->id();
            }
            $saldoSebelum = static::getSaldoSiswa($tabungan->siswa_id);

            if (
                $tabungan->jenis !== 'setor' &&
                $saldoSebelum < $tabungan->nominal
            ) {
                throw ValidationException::withMessages([
                    'nominal' => 'Saldo tidak mencukupi. Saldo saat ini: Rp '.
                        number_format($saldoSebelum, 0, ',', '.'),
                ]);
            }

            $tabungan->saldo =
                $tabungan->jenis === 'setor'
                    ? $saldoSebelum + $tabungan->nominal
                    : $saldoSebelum - $tabungan->nominal;
        });
    }
}
