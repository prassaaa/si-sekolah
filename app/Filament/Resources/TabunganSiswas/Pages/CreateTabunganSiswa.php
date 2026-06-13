<?php

namespace App\Filament\Resources\TabunganSiswas\Pages;

use App\Filament\Resources\TabunganSiswas\TabunganSiswaResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateTabunganSiswa extends CreateRecord
{
    protected static string $resource = TabunganSiswaResource::class;

    /**
     * Bungkus seluruh proses create dalam satu DB::transaction sehingga lock
     * yang diperoleh saat validasi penarikan (assertWithdrawalIsCovered) tetap
     * aktif sampai INSERT selesai — mencegah race condition tarik paralel.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(fn (): Model => parent::handleRecordCreation($data));
    }
}
