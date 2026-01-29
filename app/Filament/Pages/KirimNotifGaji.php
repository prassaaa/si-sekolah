<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class KirimNotifGaji extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static \UnitEnum|string|null $navigationGroup = 'Notifikasi';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Kirim Notif Gaji';

    protected static ?string $navigationLabel = 'Kirim Notif Gaji';

    protected static ?string $navigationBadge = 'Soon';

    protected string $view = 'filament::pages.placeholder';

    public function mount(): void
    {
        Notification::make()
            ->title('Coming Soon')
            ->body('Fitur Kirim Notifikasi Gaji via WhatsApp sedang dalam pengembangan.')
            ->info()
            ->send();

        $this->redirect(url()->previous());
    }
}
