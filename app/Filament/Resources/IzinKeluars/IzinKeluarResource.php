<?php

namespace App\Filament\Resources\IzinKeluars;

use App\Filament\Resources\IzinKeluars\Pages\CreateIzinKeluar;
use App\Filament\Resources\IzinKeluars\Pages\EditIzinKeluar;
use App\Filament\Resources\IzinKeluars\Pages\ListIzinKeluars;
use App\Filament\Resources\IzinKeluars\Pages\ViewIzinKeluar;
use App\Filament\Resources\IzinKeluars\Schemas\IzinKeluarForm;
use App\Filament\Resources\IzinKeluars\Schemas\IzinKeluarInfolist;
use App\Filament\Resources\IzinKeluars\Tables\IzinKeluarsTable;
use App\Models\IzinKeluar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class IzinKeluarResource extends Resource
{
    protected static ?string $model = IzinKeluar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowRightOnRectangle;

    protected static ?string $navigationLabel = 'Izin Keluar';

    protected static ?string $modelLabel = 'Izin Keluar';

    protected static ?string $pluralModelLabel = 'Izin Keluar';

    protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'keperluan';

    public static function form(Schema $schema): Schema
    {
        return IzinKeluarForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return IzinKeluarInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IzinKeluarsTable::configure($table);
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
            'index' => ListIzinKeluars::route('/'),
            'create' => CreateIzinKeluar::route('/create'),
            'view' => ViewIzinKeluar::route('/{record}'),
            'edit' => EditIzinKeluar::route('/{record}/edit'),
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
