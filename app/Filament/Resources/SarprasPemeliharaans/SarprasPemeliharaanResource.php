<?php

namespace App\Filament\Resources\SarprasPemeliharaans;

use App\Filament\Resources\SarprasPemeliharaans\Pages\CreateSarprasPemeliharaan;
use App\Filament\Resources\SarprasPemeliharaans\Pages\EditSarprasPemeliharaan;
use App\Filament\Resources\SarprasPemeliharaans\Pages\ListSarprasPemeliharaans;
use App\Filament\Resources\SarprasPemeliharaans\Pages\ViewSarprasPemeliharaan;
use App\Filament\Resources\SarprasPemeliharaans\Schemas\SarprasPemeliharaanForm;
use App\Filament\Resources\SarprasPemeliharaans\Schemas\SarprasPemeliharaanInfolist;
use App\Filament\Resources\SarprasPemeliharaans\Tables\SarprasPemeliharaansTable;
use App\Models\SarprasPemeliharaan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SarprasPemeliharaanResource extends Resource
{
    protected static ?string $model = SarprasPemeliharaan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Pemeliharaan';

    protected static ?string $modelLabel = 'Pemeliharaan';

    protected static ?string $pluralModelLabel = 'Pemeliharaan';

    protected static UnitEnum|string|null $navigationGroup = 'Sarana & Prasarana';

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'nomor';

    public static function getGloballySearchableAttributes(): array
    {
        return ['nomor', 'barang.nama', 'deskripsi_masalah'];
    }

    public static function form(Schema $schema): Schema
    {
        return SarprasPemeliharaanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SarprasPemeliharaanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SarprasPemeliharaansTable::configure($table);
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
            'index' => ListSarprasPemeliharaans::route('/'),
            'create' => CreateSarprasPemeliharaan::route('/create'),
            'view' => ViewSarprasPemeliharaan::route('/{record}'),
            'edit' => EditSarprasPemeliharaan::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['barang', 'pencatat']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
