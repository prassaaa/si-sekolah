<?php

namespace App\Filament\Resources\SarprasKategoris;

use App\Filament\Resources\SarprasKategoris\Pages\CreateSarprasKategori;
use App\Filament\Resources\SarprasKategoris\Pages\EditSarprasKategori;
use App\Filament\Resources\SarprasKategoris\Pages\ListSarprasKategoris;
use App\Filament\Resources\SarprasKategoris\Schemas\SarprasKategoriForm;
use App\Filament\Resources\SarprasKategoris\Tables\SarprasKategorisTable;
use App\Models\SarprasKategori;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SarprasKategoriResource extends Resource
{
    protected static ?string $model = SarprasKategori::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Kategori Sarana';

    protected static ?string $modelLabel = 'Kategori Sarana';

    protected static ?string $pluralModelLabel = 'Kategori Sarana';

    protected static UnitEnum|string|null $navigationGroup = 'Sarana & Prasarana';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return SarprasKategoriForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SarprasKategorisTable::configure($table);
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
            'index' => ListSarprasKategoris::route('/'),
            'create' => CreateSarprasKategori::route('/create'),
            'edit' => EditSarprasKategori::route('/{record}/edit'),
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
