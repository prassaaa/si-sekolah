<?php

namespace App\Filament\Resources\SarprasPemeliharaans\Pages;

use App\Filament\Resources\SarprasPemeliharaans\SarprasPemeliharaanResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSarprasPemeliharaan extends CreateRecord
{
    protected static string $resource = SarprasPemeliharaanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['dicatat_oleh'] = Auth::id();

        return $data;
    }
}
