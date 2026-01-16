<?php

namespace App\Filament\Resources\IzinPulangs;

use App\Filament\Resources\IzinPulangs\Pages\CreateIzinPulang;
use App\Filament\Resources\IzinPulangs\Pages\EditIzinPulang;
use App\Filament\Resources\IzinPulangs\Pages\ListIzinPulangs;
use App\Filament\Resources\IzinPulangs\Pages\ViewIzinPulang;
use App\Filament\Resources\IzinPulangs\Schemas\IzinPulangForm;
use App\Filament\Resources\IzinPulangs\Schemas\IzinPulangInfolist;
use App\Filament\Resources\IzinPulangs\Tables\IzinPulangsTable;
use App\Models\IzinPulang;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IzinPulangResource extends Resource
{
    protected static ?string $model = IzinPulang::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowLeftOnRectangle;

    protected static ?string $navigationLabel = 'Izin Pulang';

    protected static ?string $modelLabel = 'Izin Pulang';

    protected static ?string $pluralModelLabel = 'Izin Pulang';

    protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'alasan';

    public static function form(Schema $schema): Schema
    {
        return IzinPulangForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return IzinPulangInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IzinPulangsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIzinPulangs::route('/'),
            'create' => CreateIzinPulang::route('/create'),
            'view' => ViewIzinPulang::route('/{record}'),
            'edit' => EditIzinPulang::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
