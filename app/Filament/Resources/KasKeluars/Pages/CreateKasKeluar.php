<?php

namespace App\Filament\Resources\KasKeluars\Pages;

use App\Filament\Resources\KasKeluars\KasKeluarResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateKasKeluar extends CreateRecord
{
    protected static string $resource = KasKeluarResource::class;

    /**
     * Bungkus pembuatan record dalam DB::transaction sehingga lockForUpdate
     * di generateNomorBukti bertahan sampai INSERT selesai, menghindari
     * race condition pada nomor_bukti.
     */
    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(fn (): Model => parent::handleRecordCreation($data));
    }
}
