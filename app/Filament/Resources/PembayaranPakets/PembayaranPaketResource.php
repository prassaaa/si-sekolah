<?php

namespace App\Filament\Resources\PembayaranPakets;

use App\Filament\Resources\PembayaranPakets\Pages\CreatePembayaranPaket;
use App\Filament\Resources\PembayaranPakets\Pages\EditPembayaranPaket;
use App\Filament\Resources\PembayaranPakets\Pages\ListPembayaranPakets;
use App\Filament\Resources\PembayaranPakets\Schemas\PembayaranPaketForm;
use App\Filament\Resources\PembayaranPakets\Tables\PembayaranPaketsTable;
use App\Models\PembayaranPaket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PembayaranPaketResource extends Resource
{
    protected static ?string $model = PembayaranPaket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static UnitEnum|string|null $navigationGroup = 'Setting Pembayaran';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Pembayaran Paket';

    protected static ?string $pluralModelLabel = 'Pembayaran Paket';

    public static function form(Schema $schema): Schema
    {
        return PembayaranPaketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PembayaranPaketsTable::configure($table);
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
            'index' => ListPembayaranPakets::route('/'),
            'create' => CreatePembayaranPaket::route('/create'),
            'edit' => EditPembayaranPaket::route('/{record}/edit'),
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
