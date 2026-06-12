<?php

namespace App\Filament\Resources\SlipGajis\Pages;

use App\Filament\Resources\SlipGajis\SlipGajiResource;
use App\Models\SettingGaji;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditSlipGaji extends EditRecord
{
    protected static string $resource = SlipGajiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Hitung ulang semua field turunan dari sumber server (SettingGaji) sebelum
     * menyimpan perubahan ke database. Nilai gaji_pokok, total_tunjangan,
     * total_potongan, gaji_bersih, detail_tunjangan, dan detail_potongan yang
     * dikirim klien diabaikan sepenuhnya untuk mencegah manipulasi payload Livewire.
     *
     * Field yang sah diinput manual (tetap dari $data):
     *   pegawai_id, tahun, bulan, status, tanggal_bayar, catatan
     *
     * Field turunan yang dihitung ulang server:
     *   setting_gaji_id, gaji_pokok, total_tunjangan, total_potongan,
     *   gaji_bersih, detail_tunjangan, detail_potongan
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
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

        return $data;
    }
}
