<?php

namespace App\Filament\Resources\JurnalUmums\Pages;

use App\Filament\Resources\JurnalUmums\JurnalUmumResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditJurnalUmum extends EditRecord
{
    protected static string $resource = JurnalUmumResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record->isAutoPosted()) {
            Notification::make()
                ->title('Jurnal hasil posting otomatis hanya bisa diubah lewat dokumen sumbernya.')
                ->warning()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->record->isAutoPosted()),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
