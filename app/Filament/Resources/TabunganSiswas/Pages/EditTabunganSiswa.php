<?php

namespace App\Filament\Resources\TabunganSiswas\Pages;

use App\Filament\Resources\TabunganSiswas\TabunganSiswaResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditTabunganSiswa extends EditRecord
{
    protected static string $resource = TabunganSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * Bungkus seluruh proses update dalam satu DB::transaction sehingga lock
     * yang diperoleh saat validasi penarikan (assertWithdrawalIsCovered) tetap
     * aktif sampai UPDATE selesai — mencegah race condition tarik paralel.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(fn (): Model => parent::handleRecordUpdate($record, $data));
    }
}
