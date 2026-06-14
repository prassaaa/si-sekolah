<?php

namespace App\Filament\Resources\SarprasPengadaans;

use App\Filament\Resources\SarprasPengadaans\Pages\CreateSarprasPengadaan;
use App\Filament\Resources\SarprasPengadaans\Pages\EditSarprasPengadaan;
use App\Filament\Resources\SarprasPengadaans\Pages\ListSarprasPengadaans;
use App\Filament\Resources\SarprasPengadaans\Pages\ViewSarprasPengadaan;
use App\Filament\Resources\SarprasPengadaans\Schemas\SarprasPengadaanForm;
use App\Filament\Resources\SarprasPengadaans\Schemas\SarprasPengadaanInfolist;
use App\Filament\Resources\SarprasPengadaans\Tables\SarprasPengadaansTable;
use App\Models\SarprasPengadaan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SarprasPengadaanResource extends Resource
{
    protected static ?string $model = SarprasPengadaan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $navigationLabel = 'Pengadaan';

    protected static ?string $modelLabel = 'Pengadaan';

    protected static ?string $pluralModelLabel = 'Pengadaan';

    protected static UnitEnum|string|null $navigationGroup = 'Sarana & Prasarana';

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'nomor';

    public static function getGloballySearchableAttributes(): array
    {
        return ['nomor', 'penyedia'];
    }

    public static function form(Schema $schema): Schema
    {
        return SarprasPengadaanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SarprasPengadaanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SarprasPengadaansTable::configure($table);
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
            'index' => ListSarprasPengadaans::route('/'),
            'create' => CreateSarprasPengadaan::route('/create'),
            'view' => ViewSarprasPengadaan::route('/{record}'),
            'edit' => EditSarprasPengadaan::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
