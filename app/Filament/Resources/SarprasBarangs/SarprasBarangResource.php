<?php

namespace App\Filament\Resources\SarprasBarangs;

use App\Filament\Resources\SarprasBarangs\Pages\CreateSarprasBarang;
use App\Filament\Resources\SarprasBarangs\Pages\EditSarprasBarang;
use App\Filament\Resources\SarprasBarangs\Pages\ListSarprasBarangs;
use App\Filament\Resources\SarprasBarangs\Pages\ViewSarprasBarang;
use App\Filament\Resources\SarprasBarangs\Schemas\SarprasBarangForm;
use App\Filament\Resources\SarprasBarangs\Schemas\SarprasBarangInfolist;
use App\Filament\Resources\SarprasBarangs\Tables\SarprasBarangsTable;
use App\Models\SarprasBarang;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SarprasBarangResource extends Resource
{
    protected static ?string $model = SarprasBarang::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'Data Barang';

    protected static ?string $modelLabel = 'Barang';

    protected static ?string $pluralModelLabel = 'Data Barang';

    protected static UnitEnum|string|null $navigationGroup = 'Sarana & Prasarana';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return SarprasBarangForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SarprasBarangInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SarprasBarangsTable::configure($table);
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
            'index' => ListSarprasBarangs::route('/'),
            'create' => CreateSarprasBarang::route('/create'),
            'view' => ViewSarprasBarang::route('/{record}'),
            'edit' => EditSarprasBarang::route('/{record}/edit'),
        ];
    }
}
