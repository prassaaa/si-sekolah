<?php

namespace App\Filament\Resources\SarprasPeminjamans\Pages;

use App\Filament\Resources\SarprasPeminjamans\SarprasPeminjamanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSarprasPeminjaman extends CreateRecord
{
    protected static string $resource = SarprasPeminjamanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'dipinjam';

        return $data;
    }
}
