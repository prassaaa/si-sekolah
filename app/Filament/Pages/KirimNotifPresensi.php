<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class KirimNotifPresensi extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static \UnitEnum|string|null $navigationGroup = 'Notifikasi';

    protected static ?int $navigationSort = 30;

    protected static ?string $title = 'Kirim Notif Presensi';

    protected static ?string $navigationLabel = 'Kirim Notif Presensi';

    protected static ?string $navigationBadge = 'Soon';

    protected string $view = 'filament::pages.placeholder';

    public function mount(): void
    {
        Notification::make()
            ->title('Coming Soon')
            ->body('Fitur Kirim Notifikasi Presensi via WhatsApp sedang dalam pengembangan.')
            ->info()
            ->send();

        $this->redirect(url()->previous());
    }
}
