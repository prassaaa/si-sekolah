<?php

namespace App\Filament\Resources\SlipGajis\Pages;

use App\Filament\Resources\SlipGajis\SlipGajiResource;
use App\Models\Pajak;
use App\Models\SettingGaji;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSlipGaji extends CreateRecord
{
    protected static string $resource = SlipGajiResource::class;

    /**
     * Hitung ulang semua field turunan dari sumber server (SettingGaji) sebelum
     * menyimpan ke database. Nilai gaji_pokok, total_tunjangan, total_potongan,
     * gaji_bersih, detail_tunjangan, dan detail_potongan yang dikirim klien
     * diabaikan sepenuhnya untuk mencegah manipulasi payload Livewire.
     *
     * Field yang sah diinput manual (tetap dari $data):
     *   pegawai_id, tahun, bulan, status, tanggal_bayar, catatan, pajak_id
     *
     * Field turunan yang dihitung ulang server:
     *   setting_gaji_id, gaji_pokok, total_tunjangan, total_potongan,
     *   potongan_pajak, gaji_bersih, detail_tunjangan, detail_potongan
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $setting = SettingGaji::where('pegawai_id', $data['pegawai_id'])
            ->where('is_active', true)
            ->first();

        if (! $setting) {
            throw ValidationException::withMessages([
                'pegawai_id' => 'Setting gaji aktif untuk pegawai ini tidak ditemukan.',
            ]);
        }

        $data['setting_gaji_id'] = $setting->id;
        $data['gaji_pokok'] = $setting->gaji_pokok;
        $data['total_tunjangan'] = $setting->total_tunjangan;
        $data['total_potongan'] = $setting->total_potongan;
        $data['gaji_bersih'] = $setting->gaji_bersih;
        $data['detail_tunjangan'] = [
            'jabatan' => $setting->tunjangan_jabatan,
            'kehadiran' => $setting->tunjangan_kehadiran,
            'transport' => $setting->tunjangan_transport,
            'makan' => $setting->tunjangan_makan,
            'lainnya' => $setting->tunjangan_lainnya,
        ];
        $data['detail_potongan'] = [
            'bpjs' => $setting->potongan_bpjs,
            'pph21' => $setting->potongan_pph21,
            'lainnya' => $setting->potongan_lainnya,
        ];

        return $this->applyPotonganPajak($data);
    }

    /**
     * Hitung ulang potongan pajak (PPh) secara aditif lalu sesuaikan
     * total_potongan & gaji_bersih. Aman dijalankan pada Create maupun Edit.
     *
     * Bila pajak_id terisi & merujuk master pajak aktif:
     *   potongan_pajak = round(persentase% x (gaji_pokok + total_tunjangan), 2)
     *   total_potongan = total_potongan_setting + potongan_pajak
     *   gaji_bersih    = (gaji_pokok + total_tunjangan) - total_potongan
     *
     * Bila pajak_id kosong/tidak aktif: potongan_pajak = 0 dan nilai
     * total_potongan/gaji_bersih dari SettingGaji dibiarkan apa adanya, sehingga
     * perilaku & jurnal slip tanpa pajak identik dengan Wave 2 (aditif murni).
     *
     * Nilai potongan_pajak kiriman klien diabaikan total (pola Wave 0).
     * Perhitungan memakai bcmath agar konsisten dengan presisi string-desimal
     * SettingGaji dan menghindari galat pembulatan float.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyPotonganPajak(array $data): array
    {
        $pajak = ! empty($data['pajak_id'])
            ? Pajak::query()->active()->find($data['pajak_id'])
            : null;

        if ($pajak === null) {
            $data['pajak_id'] = null;
            $data['potongan_pajak'] = '0.00';

            return $data;
        }

        $dasar = bcadd((string) $data['gaji_pokok'], (string) $data['total_tunjangan'], 2);
        $potonganPajak = bcdiv(bcmul($dasar, (string) $pajak->persentase, 4), '100', 2);

        $data['potongan_pajak'] = $potonganPajak;
        $data['total_potongan'] = bcadd((string) $data['total_potongan'], $potonganPajak, 2);
        $data['gaji_bersih'] = bcsub($dasar, (string) $data['total_potongan'], 2);

        return $data;
    }

    /**
     * Wrap the record creation in a database transaction so the lockForUpdate
     * acquired while generating the slip nomor (SlipGaji::booted creating hook)
     * is held until the row is inserted. Without an enclosing transaction the
     * lock would be released immediately, reopening the race that lets two
     * concurrent creates read the same max nomor and collide on the unique key.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(fn (): Model => parent::handleRecordCreation($data));
    }
}
