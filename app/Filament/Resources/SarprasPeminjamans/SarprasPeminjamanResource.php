<?php

namespace App\Filament\Resources\SarprasPeminjamans;

use App\Filament\Resources\SarprasPeminjamans\Pages\CreateSarprasPeminjaman;
use App\Filament\Resources\SarprasPeminjamans\Pages\EditSarprasPeminjaman;
use App\Filament\Resources\SarprasPeminjamans\Pages\ListSarprasPeminjamans;
use App\Filament\Resources\SarprasPeminjamans\Pages\ViewSarprasPeminjaman;
use App\Filament\Resources\SarprasPeminjamans\Schemas\SarprasPeminjamanForm;
use App\Filament\Resources\SarprasPeminjamans\Schemas\SarprasPeminjamanInfolist;
use App\Filament\Resources\SarprasPeminjamans\Tables\SarprasPeminjamansTable;
use App\Models\SarprasPeminjaman;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SarprasPeminjamanResource extends Resource
{
    protected static ?string $model = SarprasPeminjaman::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'Peminjaman';

    protected static ?string $modelLabel = 'Peminjaman Sarana';

    protected static ?string $pluralModelLabel = 'Peminjaman Sarana';

    protected static UnitEnum|string|null $navigationGroup = 'Sarana & Prasarana';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return SarprasPeminjamanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SarprasPeminjamanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SarprasPeminjamansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSarprasPeminjamans::route('/'),
            'create' => CreateSarprasPeminjaman::route('/create'),
            'view' => ViewSarprasPeminjaman::route('/{record}'),
            'edit' => EditSarprasPeminjaman::route('/{record}/edit'),
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
