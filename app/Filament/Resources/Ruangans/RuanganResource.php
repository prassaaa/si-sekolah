<?php

namespace App\Filament\Resources\Ruangans;

use App\Filament\Resources\Ruangans\Pages\CreateRuangan;
use App\Filament\Resources\Ruangans\Pages\EditRuangan;
use App\Filament\Resources\Ruangans\Pages\ListRuangans;
use App\Filament\Resources\Ruangans\Schemas\RuanganForm;
use App\Filament\Resources\Ruangans\Tables\RuangansTable;
use App\Models\Ruangan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RuanganResource extends Resource
{
    protected static ?string $model = Ruangan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Ruangan / Lokasi';

    protected static ?string $modelLabel = 'Ruangan';

    protected static ?string $pluralModelLabel = 'Ruangan';

    protected static UnitEnum|string|null $navigationGroup = 'Sarana & Prasarana';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return RuanganForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RuangansTable::configure($table);
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
            'index' => ListRuangans::route('/'),
            'create' => CreateRuangan::route('/create'),
            'edit' => EditRuangan::route('/{record}/edit'),
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
