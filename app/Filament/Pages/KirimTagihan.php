<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class KirimTagihan extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static \UnitEnum|string|null $navigationGroup = 'Notifikasi';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Kirim Tagihan';

    protected static ?string $navigationLabel = 'Kirim Tagihan';

    protected static ?string $navigationBadge = 'Soon';

    protected string $view = 'filament::pages.placeholder';

    public function mount(): void
    {
        Notification::make()
            ->title('Coming Soon')
            ->body('Fitur Kirim Tagihan via WhatsApp sedang dalam pengembangan.')
            ->info()
            ->send();

        $this->redirect(url()->previous());
    }
}
