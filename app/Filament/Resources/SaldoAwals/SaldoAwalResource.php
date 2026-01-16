<?php

namespace App\Filament\Resources\SaldoAwals;

use App\Filament\Resources\SaldoAwals\Pages\CreateSaldoAwal;
use App\Filament\Resources\SaldoAwals\Pages\EditSaldoAwal;
use App\Filament\Resources\SaldoAwals\Pages\ListSaldoAwals;
use App\Filament\Resources\SaldoAwals\Schemas\SaldoAwalForm;
use App\Filament\Resources\SaldoAwals\Tables\SaldoAwalsTable;
use App\Models\SaldoAwal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SaldoAwalResource extends Resource
{
    protected static ?string $model = SaldoAwal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static UnitEnum|string|null $navigationGroup = 'Kas & Bank';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Saldo Awal';

    protected static ?string $pluralModelLabel = 'Saldo Awal';

    public static function form(Schema $schema): Schema
    {
        return SaldoAwalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SaldoAwalsTable::configure($table);
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
            'index' => ListSaldoAwals::route('/'),
            'create' => CreateSaldoAwal::route('/create'),
            'edit' => EditSaldoAwal::route('/{record}/edit'),
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
