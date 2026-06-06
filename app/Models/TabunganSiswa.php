<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TabunganSiswa extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    private const MONEY_SCALE = 2;

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
        return (float) static::query()
            ->where('siswa_id', $siswaId)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->value('saldo') ?? 0.0;
    }

    protected static function booted(): void
    {
        static::creating(function (TabunganSiswa $tabungan): void {
            if (! $tabungan->user_id) {
                $tabungan->user_id = auth()->id();
            }

            $tabungan->assertWithdrawalIsCovered();
        });

        static::updating(function (TabunganSiswa $tabungan): void {
            $tabungan->assertWithdrawalIsCovered();
        });

        static::created(function (TabunganSiswa $tabungan): void {
            $tabungan->recalculateSaldoFor((int) $tabungan->siswa_id);
        });

        static::updated(function (TabunganSiswa $tabungan): void {
            $tabungan->recalculateSaldoFor((int) $tabungan->siswa_id);

            $originalSiswaId = $tabungan->getOriginal('siswa_id');

            if ($originalSiswaId !== null && (int) $originalSiswaId !== (int) $tabungan->siswa_id) {
                $tabungan->recalculateSaldoFor((int) $originalSiswaId);
            }
        });

        static::deleted(function (TabunganSiswa $tabungan): void {
            $tabungan->recalculateSaldoFor((int) $tabungan->siswa_id);
        });

        static::restored(function (TabunganSiswa $tabungan): void {
            $tabungan->recalculateSaldoFor((int) $tabungan->siswa_id);
        });

        static::forceDeleted(function (TabunganSiswa $tabungan): void {
            $tabungan->recalculateSaldoFor((int) $tabungan->siswa_id);
        });
    }

    /**
     * Reject a `tarik` row before it is persisted when it would drive the
     * running balance negative at its chronological position.
     *
     * The balance is computed under a row lock against the affected student's
     * other rows ordered by tanggal then id, so the check reflects the real
     * (recomputed) balance rather than the stale stored saldo.
     *
     * @throws ValidationException
     */
    private function assertWithdrawalIsCovered(): void
    {
        if ($this->jenis !== 'tarik') {
            return;
        }

        DB::transaction(function (): void {
            $rows = static::query()
                ->where('siswa_id', $this->siswa_id)
                ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
                ->orderBy('tanggal')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $runningSaldo = '0.00';
            $tanggal = $this->tanggal instanceof \DateTimeInterface
                ? $this->tanggal->format('Y-m-d')
                : (string) $this->tanggal;

            foreach ($rows as $row) {
                $rowTanggal = $row->tanggal instanceof \DateTimeInterface
                    ? $row->tanggal->format('Y-m-d')
                    : (string) $row->tanggal;

                if ($rowTanggal > $tanggal) {
                    continue;
                }

                $nominal = bcadd((string) $row->nominal, '0', self::MONEY_SCALE);
                $runningSaldo = $row->jenis === 'setor'
                    ? bcadd($runningSaldo, $nominal, self::MONEY_SCALE)
                    : bcsub($runningSaldo, $nominal, self::MONEY_SCALE);
            }

            $thisNominal = bcadd((string) $this->nominal, '0', self::MONEY_SCALE);
            $runningSaldo = bcsub($runningSaldo, $thisNominal, self::MONEY_SCALE);

            if (bccomp($runningSaldo, '0', self::MONEY_SCALE) < 0) {
                throw ValidationException::withMessages([
                    'nominal' => 'Saldo tidak mencukupi untuk penarikan ini.',
                ]);
            }
        });
    }

    /**
     * Recompute the running balance (saldo) for every non-deleted row of a
     * student, ordered by tanggal then id, inside a locked transaction.
     *
     * Validates that no `tarik` row drives the running balance negative and
     * persists the corrected saldo for each affected row.
     *
     * @throws ValidationException
     */
    private function recalculateSaldoFor(int $siswaId): void
    {
        DB::transaction(function () use ($siswaId): void {
            $rows = static::query()
                ->where('siswa_id', $siswaId)
                ->orderBy('tanggal')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $runningSaldo = '0.00';

            foreach ($rows as $row) {
                $nominal = bcadd((string) $row->nominal, '0', self::MONEY_SCALE);

                if ($row->jenis === 'setor') {
                    $runningSaldo = bcadd($runningSaldo, $nominal, self::MONEY_SCALE);
                } else {
                    $runningSaldo = bcsub($runningSaldo, $nominal, self::MONEY_SCALE);

                    if (bccomp($runningSaldo, '0', self::MONEY_SCALE) < 0) {
                        throw ValidationException::withMessages([
                            'nominal' => 'Saldo tidak mencukupi untuk penarikan ini.',
                        ]);
                    }
                }

                $newSaldo = bcadd($runningSaldo, '0', self::MONEY_SCALE);

                if (bccomp((string) $row->saldo, $newSaldo, self::MONEY_SCALE) !== 0) {
                    static::query()
                        ->whereKey($row->getKey())
                        ->update(['saldo' => $newSaldo]);
                }
            }
        });
    }
}
