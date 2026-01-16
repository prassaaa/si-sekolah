<?php

namespace App\Filament\Resources\UnitPos;

use App\Filament\Resources\UnitPos\Pages\CreateUnitPos;
use App\Filament\Resources\UnitPos\Pages\EditUnitPos;
use App\Filament\Resources\UnitPos\Pages\ListUnitPos;
use App\Filament\Resources\UnitPos\Schemas\UnitPosForm;
use App\Filament\Resources\UnitPos\Tables\UnitPosTable;
use App\Models\UnitPos;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class UnitPosResource extends Resource
{
    protected static ?string $model = UnitPos::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static UnitEnum|string|null $navigationGroup = 'Setting Pembayaran';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Unit POS';

    protected static ?string $pluralModelLabel = 'Unit POS';

    public static function form(Schema $schema): Schema
    {
        return UnitPosForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitPosTable::configure($table);
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
            'index' => ListUnitPos::route('/'),
            'create' => CreateUnitPos::route('/create'),
            'edit' => EditUnitPos::route('/{record}/edit'),
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
