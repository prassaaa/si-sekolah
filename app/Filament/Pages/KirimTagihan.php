<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class KirimTagihan extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static \UnitEnum|string|null $navigationGroup = 'Notifikasi';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Kirim Tagihan';

    protected static ?string $navigationLabel = 'Kirim Tagihan';

    protected string $view = 'filament.pages.kirim-tagihan';
}
