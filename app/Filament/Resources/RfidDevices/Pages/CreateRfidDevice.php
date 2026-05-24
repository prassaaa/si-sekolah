<?php

namespace App\Filament\Resources\RfidDevices\Pages;

use App\Filament\Resources\RfidDevices\RfidDeviceResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateRfidDevice extends CreateRecord
{
    protected static string $resource = RfidDeviceResource::class;

    protected ?string $plainTokenForDisplay = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->plainTokenForDisplay = Str::random(60);
        $data['api_token'] = Hash::make($this->plainTokenForDisplay);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->plainTokenForDisplay) {
            Notification::make()
                ->title('Device berhasil dibuat')
                ->body('API Token (sekali tampil): '.$this->plainTokenForDisplay.' — copy & simpan sekarang ke firmware device.')
                ->success()
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
