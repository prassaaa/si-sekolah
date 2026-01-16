<?php

namespace App\Filament\Resources\PosBayars;

use App\Filament\Resources\PosBayars\Pages\CreatePosBayar;
use App\Filament\Resources\PosBayars\Pages\EditPosBayar;
use App\Filament\Resources\PosBayars\Pages\ListPosBayars;
use App\Filament\Resources\PosBayars\Schemas\PosBayarForm;
use App\Filament\Resources\PosBayars\Tables\PosBayarsTable;
use App\Models\PosBayar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PosBayarResource extends Resource
{
    protected static ?string $model = PosBayar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static UnitEnum|string|null $navigationGroup = 'Setting Pembayaran';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Pos Bayar';

    protected static ?string $pluralModelLabel = 'Pos Bayar';

    public static function form(Schema $schema): Schema
    {
        return PosBayarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PosBayarsTable::configure($table);
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
            'index' => ListPosBayars::route('/'),
            'create' => CreatePosBayar::route('/create'),
            'edit' => EditPosBayar::route('/{record}/edit'),
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
