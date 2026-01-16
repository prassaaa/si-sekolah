<?php

namespace App\Filament\Resources\KasKeluars;

use App\Filament\Resources\KasKeluars\Pages\CreateKasKeluar;
use App\Filament\Resources\KasKeluars\Pages\EditKasKeluar;
use App\Filament\Resources\KasKeluars\Pages\ListKasKeluars;
use App\Filament\Resources\KasKeluars\Schemas\KasKeluarForm;
use App\Filament\Resources\KasKeluars\Tables\KasKeluarsTable;
use App\Models\KasKeluar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class KasKeluarResource extends Resource
{
    protected static ?string $model = KasKeluar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpOnSquare;

    protected static UnitEnum|string|null $navigationGroup = 'Kas & Bank';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Kas Keluar';

    protected static ?string $pluralModelLabel = 'Kas Keluar';

    public static function form(Schema $schema): Schema
    {
        return KasKeluarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KasKeluarsTable::configure($table);
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
            'index' => ListKasKeluars::route('/'),
            'create' => CreateKasKeluar::route('/create'),
            'edit' => EditKasKeluar::route('/{record}/edit'),
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
