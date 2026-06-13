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
     * running balance negative at any point in the student's timeline.
     *
     * This method MUST be called inside an active DB::transaction so that the
     * lockForUpdate acquired here is held until the INSERT/UPDATE commits —
     * preventing concurrent withdrawals from racing past the balance check.
     *
     * Beyond the insertion point, the method also simulates how every subsequent
     * row in the ledger would be affected by this new entry so that a backdated
     * withdrawal cannot silently corrupt future balances.
     *
     * @throws ValidationException
     */
    private function assertWithdrawalIsCovered(): void
    {
        if ($this->jenis !== 'tarik') {
            return;
        }

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

        /** @var bool $injected — have we applied the new withdrawal yet? */
        $injected = false;

        foreach ($rows as $row) {
            $rowTanggal = $row->tanggal instanceof \DateTimeInterface
                ? $row->tanggal->format('Y-m-d')
                : (string) $row->tanggal;

            // Inject the new withdrawal into the timeline at the correct
            // chronological position before processing rows that come after it.
            if (! $injected && $rowTanggal > $tanggal) {
                $thisNominal = bcadd((string) $this->nominal, '0', self::MONEY_SCALE);
                $runningSaldo = bcsub($runningSaldo, $thisNominal, self::MONEY_SCALE);

                if (bccomp($runningSaldo, '0', self::MONEY_SCALE) < 0) {
                    throw ValidationException::withMessages([
                        'nominal' => 'Saldo tidak mencukupi untuk penarikan ini.',
                    ]);
                }

                $injected = true;
            }

            $nominal = bcadd((string) $row->nominal, '0', self::MONEY_SCALE);
            $runningSaldo = $row->jenis === 'setor'
                ? bcadd($runningSaldo, $nominal, self::MONEY_SCALE)
                : bcsub($runningSaldo, $nominal, self::MONEY_SCALE);

            // A tarik row after the injection point must not drive the balance
            // negative — that would mean this backdated withdrawal corrupts a
            // future row that was previously valid.
            if ($injected && $row->jenis === 'tarik' && bccomp($runningSaldo, '0', self::MONEY_SCALE) < 0) {
                throw ValidationException::withMessages([
                    'nominal' => 'Penarikan ini menyebabkan saldo negatif pada transaksi berikutnya (backdated).',
                ]);
            }
        }

        // The new withdrawal is the last (or only) entry — inject at the end.
        if (! $injected) {
            $thisNominal = bcadd((string) $this->nominal, '0', self::MONEY_SCALE);
            $runningSaldo = bcsub($runningSaldo, $thisNominal, self::MONEY_SCALE);

            if (bccomp($runningSaldo, '0', self::MONEY_SCALE) < 0) {
                throw ValidationException::withMessages([
                    'nominal' => 'Saldo tidak mencukupi untuk penarikan ini.',
                ]);
            }
        }
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
