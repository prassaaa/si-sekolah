<?php

namespace App\Filament\Resources\TagihanSiswas\Pages;

use App\Filament\Resources\TagihanSiswas\TagihanSiswaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTagihanSiswa extends CreateRecord
{
    protected static string $resource = TagihanSiswaResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['total_terbayar'] = 0;
        $data['sisa_tagihan'] = $data['total_tagihan'];

        return $data;
    }
}
