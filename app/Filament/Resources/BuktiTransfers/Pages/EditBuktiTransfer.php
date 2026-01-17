<?php

namespace App\Filament\Resources\BuktiTransfers\Pages;

use App\Filament\Resources\BuktiTransfers\BuktiTransferResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditBuktiTransfer extends EditRecord
{
    protected static string $resource = BuktiTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
