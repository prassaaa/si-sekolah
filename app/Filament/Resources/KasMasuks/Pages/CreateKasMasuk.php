<?php

namespace App\Filament\Resources\KasMasuks\Pages;

use App\Filament\Resources\KasMasuks\KasMasukResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateKasMasuk extends CreateRecord
{
    protected static string $resource = KasMasukResource::class;

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
