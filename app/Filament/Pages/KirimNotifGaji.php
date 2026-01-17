<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class KirimNotifGaji extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static \UnitEnum|string|null $navigationGroup = 'Notifikasi';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Kirim Notif Gaji';

    protected static ?string $navigationLabel = 'Kirim Notif Gaji';

    protected string $view = 'filament.pages.kirim-notif-gaji';
}
