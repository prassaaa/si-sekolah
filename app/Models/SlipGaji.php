<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
        'tanggal_bayar',
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

    protected static function booted(): void
    {
        static::creating(function (SlipGaji $slip) {
            if (empty($slip->nomor)) {
                $prefix = 'SG-'.date('Ym').'-';

                $lastNomor = DB::table('slip_gajis')
                    ->where('nomor', 'like', 'SG-'.date('Y').'%')
                    ->max('nomor');

                $lastNumber = $lastNomor ? (int) substr($lastNomor, -4) : 0;

                $slip->nomor =
                    $prefix.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            }
            if (! $slip->created_by) {
                $slip->created_by = auth()->id();
            }
        });
    }
}
