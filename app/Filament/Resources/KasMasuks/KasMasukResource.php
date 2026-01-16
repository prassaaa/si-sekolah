<?php

namespace App\Filament\Resources\KasMasuks;

use App\Filament\Resources\KasMasuks\Pages\CreateKasMasuk;
use App\Filament\Resources\KasMasuks\Pages\EditKasMasuk;
use App\Filament\Resources\KasMasuks\Pages\ListKasMasuks;
use App\Filament\Resources\KasMasuks\Schemas\KasMasukForm;
use App\Filament\Resources\KasMasuks\Tables\KasMasuksTable;
use App\Models\KasMasuk;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class KasMasukResource extends Resource
{
    protected static ?string $model = KasMasuk::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownOnSquare;

    protected static UnitEnum|string|null $navigationGroup = 'Kas & Bank';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Kas Masuk';

    protected static ?string $pluralModelLabel = 'Kas Masuk';

    public static function form(Schema $schema): Schema
    {
        return KasMasukForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KasMasuksTable::configure($table);
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
            'index' => ListKasMasuks::route('/'),
            'create' => CreateKasMasuk::route('/create'),
            'edit' => EditKasMasuk::route('/{record}/edit'),
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
