<?php

namespace App\Filament\Resources\Konselings;

use App\Filament\Resources\Konselings\Pages\CreateKonseling;
use App\Filament\Resources\Konselings\Pages\EditKonseling;
use App\Filament\Resources\Konselings\Pages\ListKonselings;
use App\Filament\Resources\Konselings\Pages\ViewKonseling;
use App\Filament\Resources\Konselings\Schemas\KonselingForm;
use App\Filament\Resources\Konselings\Schemas\KonselingInfolist;
use App\Filament\Resources\Konselings\Tables\KonselingsTable;
use App\Models\Konseling;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class KonselingResource extends Resource
{
    protected static ?string $model = Konseling::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Konseling';

    protected static ?string $modelLabel = 'Konseling';

    protected static ?string $pluralModelLabel = 'Konseling';

    protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'permasalahan';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('perlu_tindak_lanjut', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('perlu_tindak_lanjut', true)->count() > 0 ? 'warning' : 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return KonselingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KonselingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KonselingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKonselings::route('/'),
            'create' => CreateKonseling::route('/create'),
            'view' => ViewKonseling::route('/{record}'),
            'edit' => EditKonseling::route('/{record}/edit'),
        ];
    }
}
