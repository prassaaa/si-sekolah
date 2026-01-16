<?php

namespace App\Filament\Resources\TabunganSiswas;

use App\Filament\Resources\TabunganSiswas\Pages\CreateTabunganSiswa;
use App\Filament\Resources\TabunganSiswas\Pages\EditTabunganSiswa;
use App\Filament\Resources\TabunganSiswas\Pages\ListTabunganSiswas;
use App\Filament\Resources\TabunganSiswas\Schemas\TabunganSiswaForm;
use App\Filament\Resources\TabunganSiswas\Tables\TabunganSiswasTable;
use App\Models\TabunganSiswa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TabunganSiswaResource extends Resource
{
    protected static ?string $model = TabunganSiswa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static UnitEnum|string|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Tabungan Siswa';

    protected static ?string $pluralModelLabel = 'Tabungan Siswa';

    public static function form(Schema $schema): Schema
    {
        return TabunganSiswaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TabunganSiswasTable::configure($table);
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
            'index' => ListTabunganSiswas::route('/'),
            'create' => CreateTabunganSiswa::route('/create'),
            'edit' => EditTabunganSiswa::route('/{record}/edit'),
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
