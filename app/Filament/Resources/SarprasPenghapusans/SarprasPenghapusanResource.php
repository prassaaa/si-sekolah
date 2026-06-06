<?php

namespace App\Filament\Resources\SarprasPenghapusans;

use App\Filament\Resources\SarprasPenghapusans\Pages\CreateSarprasPenghapusan;
use App\Filament\Resources\SarprasPenghapusans\Pages\EditSarprasPenghapusan;
use App\Filament\Resources\SarprasPenghapusans\Pages\ListSarprasPenghapusans;
use App\Filament\Resources\SarprasPenghapusans\Pages\ViewSarprasPenghapusan;
use App\Filament\Resources\SarprasPenghapusans\Schemas\SarprasPenghapusanForm;
use App\Filament\Resources\SarprasPenghapusans\Tables\SarprasPenghapusansTable;
use App\Models\SarprasPenghapusan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SarprasPenghapusanResource extends Resource
{
    protected static ?string $model = SarprasPenghapusan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    protected static ?string $navigationLabel = 'Penghapusan';

    protected static ?string $modelLabel = 'Penghapusan Aset';

    protected static ?string $pluralModelLabel = 'Penghapusan Aset';

    protected static UnitEnum|string|null $navigationGroup = 'Sarana & Prasarana';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'nomor';

    public static function form(Schema $schema): Schema
    {
        return SarprasPenghapusanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SarprasPenghapusansTable::configure($table);
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
            'index' => ListSarprasPenghapusans::route('/'),
            'create' => CreateSarprasPenghapusan::route('/create'),
            'view' => ViewSarprasPenghapusan::route('/{record}'),
            'edit' => EditSarprasPenghapusan::route('/{record}/edit'),
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
