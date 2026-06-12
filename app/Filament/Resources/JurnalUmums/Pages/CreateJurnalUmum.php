<?php

namespace App\Filament\Resources\JurnalUmums\Pages;

use App\Filament\Resources\JurnalUmums\JurnalUmumResource;
use App\Filament\Resources\JurnalUmums\Schemas\JurnalUmumCreateForm;
use App\Models\JurnalUmum;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateJurnalUmum extends CreateRecord
{
    protected static string $resource = JurnalUmumResource::class;

    /**
     * Override schema form halaman Create: pakai multi-baris (double-entry).
     * Halaman Edit tetap menggunakan JurnalUmumForm (single-leg via Resource::form()).
     */
    public function form(Schema $schema): Schema
    {
        return JurnalUmumCreateForm::configure($schema);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Buat entri jurnal double-entry dalam satu transaksi atomik.
     *
     * Alur:
     *   (a) Validasi server-side total debit = total kredit dan total > 0.
     *   (b) Generate nomor_bukti sekuensial JU-{Ymd}-{####} atomik (lock dalam
     *       transaksi yang sama, insert di transaksi yang sama).
     *   (c) Create satu baris JurnalUmum per detail dengan nomor_bukti sama.
     *   (d) Return record pertama (diperlukan oleh CreateRecord).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    protected function handleRecordCreation(array $data): Model
    {
        $details = $data['details'] ?? [];

        $totalDebitStr = number_format(
            array_sum(array_column($details, 'debit')),
            2, '.', '',
        );
        $totalKreditStr = number_format(
            array_sum(array_column($details, 'kredit')),
            2, '.', '',
        );

        if (bccomp($totalDebitStr, $totalKreditStr, 2) !== 0) {
            throw ValidationException::withMessages([
                'data.details' => 'Total debit (Rp '.number_format((float) $totalDebitStr, 2, ',', '.').')'.
                    ' harus sama dengan total kredit (Rp '.number_format((float) $totalKreditStr, 2, ',', '.').').'.
                    ' Periksa kembali baris-baris jurnal Anda.',
            ]);
        }

        if (bccomp($totalDebitStr, '0.00', 2) <= 0) {
            throw ValidationException::withMessages([
                'data.details' => 'Total debit dan kredit tidak boleh nol. Masukkan nominal yang valid.',
            ]);
        }

        return DB::transaction(function () use ($data, $details): JurnalUmum {
            $tanggal = $data['tanggal'];
            $keterangan = $data['keterangan'] ?? null;
            $createdBy = Auth::id();
            $ym = now()->format('Ymd');
            $prefix = 'JU-'.$ym.'-';

            $lastNomor = DB::table('jurnal_umums')
                ->lockForUpdate()
                ->where('nomor_bukti', 'like', 'JU-'.$ym.'%')
                ->whereNull('deleted_at')
                ->orderByDesc('nomor_bukti')
                ->value('nomor_bukti');

            $lastNumber = $lastNomor ? (int) substr($lastNomor, -4) : 0;
            $nomorBukti = $prefix.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

            $firstRecord = null;

            foreach ($details as $baris) {
                $record = JurnalUmum::create([
                    'nomor_bukti' => $nomorBukti,
                    'tanggal' => $tanggal,
                    'keterangan' => $keterangan,
                    'akun_id' => $baris['akun_id'],
                    'debit' => $baris['debit'] ?? 0,
                    'kredit' => $baris['kredit'] ?? 0,
                    'referensi' => null,
                    'jenis_referensi' => null,
                    'referensi_id' => null,
                    'created_by' => $createdBy,
                ]);

                if ($firstRecord === null) {
                    $firstRecord = $record;
                }
            }

            /** @var JurnalUmum $firstRecord */
            return $firstRecord;
        });
    }
}
