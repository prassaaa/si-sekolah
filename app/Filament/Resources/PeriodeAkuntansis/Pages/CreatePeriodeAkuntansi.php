<?php

namespace App\Filament\Resources\PeriodeAkuntansis\Pages;

use App\Filament\Resources\PeriodeAkuntansis\PeriodeAkuntansiResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePeriodeAkuntansi extends CreateRecord
{
    protected static string $resource = PeriodeAkuntansiResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Periode selalu dibuat dalam keadaan terbuka; penutupan dilakukan melalui
     * action "Tutup Periode" di daftar.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'open';
        $data['closed_by'] = null;
        $data['closed_at'] = null;

        return $data;
    }
}
