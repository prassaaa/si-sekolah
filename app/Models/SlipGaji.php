<?php

namespace App\Models;

use App\Observers\SlipGajiObserver;
use App\Services\Accounting\SlipGajiJournalPoster;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy(SlipGajiObserver::class)]
class SlipGaji extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'nomor',
        'pegawai_id',
        'setting_gaji_id',
        'tahun',
        'bulan',
        'gaji_pokok',
        'total_tunjangan',
        'total_potongan',
        'gaji_bersih',
        'detail_tunjangan',
        'detail_potongan',
        'status',
        'approved_at',
        'paid_at',
        'tanggal_bayar',
        'kas_keluar_id',
        'catatan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'gaji_pokok' => 'decimal:2',
            'total_tunjangan' => 'decimal:2',
            'total_potongan' => 'decimal:2',
            'gaji_bersih' => 'decimal:2',
            'detail_tunjangan' => 'array',
            'detail_potongan' => 'array',
            'tanggal_bayar' => 'date',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'kas_keluar_id' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['pegawai_id', 'tahun', 'bulan', 'gaji_bersih', 'status'])
            ->logOnlyDirty()
            ->useLogName('slip_gaji');
    }

    public function pegawai(): BelongsTo
    {
        return $this->belongsTo(Pegawai::class);
    }

    public function settingGaji(): BelongsTo
    {
        return $this->belongsTo(SettingGaji::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * KasKeluar pembayaran gaji yang dibuat saat slip dibayar.
     *
     * @return BelongsTo<KasKeluar, $this>
     */
    public function kasKeluar(): BelongsTo
    {
        return $this->belongsTo(KasKeluar::class);
    }

    public function getNamaBulanAttribute(): string
    {
        $bulanList = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        return $bulanList[$this->bulan] ?? '';
    }

    public function getPeriodeAttribute(): string
    {
        return $this->nama_bulan.' '.$this->tahun;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Setujui slip: transisi draft -> approved lalu akrualkan beban gaji.
     *
     * Akrual: D Beban Gaji (guru/karyawan) / K Hutang Gaji, diposting otomatis
     * oleh SlipGajiJournalPoster (idempoten, tunduk pada cut-off). Hanya berlaku
     * dari status 'draft'; pemanggilan ulang menjadi no-op.
     */
    public function approve(): void
    {
        if (! $this->isDraft()) {
            return;
        }

        DB::transaction(function (): void {
            $this->status = 'approved';
            $this->approved_at = now();
            $this->save();

            app(SlipGajiJournalPoster::class)->postAkrual($this);
        });
    }

    /**
     * Bayar slip: transisi approved -> paid dengan membuat KasKeluar.
     *
     * KasKeluar [D Hutang Gaji / K Kas] dibuat lewat Eloquent sehingga
     * KasKeluarObserver otomatis menjurnalnya. Idempoten: hanya berlaku dari
     * status 'approved' dengan kas_keluar_id masih null.
     */
    public function bayar(): void
    {
        if (! $this->isApproved() || $this->kas_keluar_id !== null) {
            return;
        }

        DB::transaction(function (): void {
            $hutangAkunId = Akun::query()
                ->where('kode', config('akuntansi.akun.hutang_gaji'))
                ->value('id');
            $kasAkunId = Akun::query()
                ->where('kode', config('akuntansi.akun.kas_default'))
                ->value('id');

            $kasKeluar = KasKeluar::create([
                'akun_id' => $hutangAkunId,
                'kas_akun_id' => $kasAkunId,
                'tanggal' => now(),
                'nominal' => $this->gaji_bersih,
                'penerima' => $this->pegawai?->nama,
                'keterangan' => 'Pembayaran gaji '.$this->nomor,
                'user_id' => auth()->id(),
            ]);

            $this->kas_keluar_id = $kasKeluar->id;
            $this->status = 'paid';
            $this->paid_at = now();
            $this->tanggal_bayar = now()->toDateString();
            $this->save();
        });
    }

    protected static function booted(): void
    {
        static::creating(function (SlipGaji $slip) {
            if (empty($slip->nomor)) {
                // Use the slip's own tahun/bulan for back-dated numbering correctness.
                $tahun = $slip->tahun ?? date('Y');
                $bulan = $slip->bulan ?? date('n');
                $ym = $tahun.str_pad($bulan, 2, '0', STR_PAD_LEFT);
                $prefix = 'SG-'.$ym.'-';

                // lockForUpdate must hold until the slip is inserted. The
                // surrounding DB::transaction is opened by the caller (e.g.
                // CreateSlipGaji::handleRecordCreation) so the row-range lock is
                // not released between reading the max nomor and the insert.
                $lastNomor = DB::table('slip_gajis')
                    ->lockForUpdate()
                    ->where('nomor', 'like', 'SG-'.$ym.'%')
                    ->max('nomor');

                $lastNumber = $lastNomor ? (int) substr($lastNomor, -4) : 0;

                $slip->nomor = $prefix.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            }
            if (! $slip->created_by) {
                $slip->created_by = auth()->id();
            }
        });
    }
}
