<?php

namespace App\Filament\Resources\KategoriPembayarans;

use App\Filament\Resources\KategoriPembayarans\Pages\CreateKategoriPembayaran;
use App\Filament\Resources\KategoriPembayarans\Pages\EditKategoriPembayaran;
use App\Filament\Resources\KategoriPembayarans\Pages\ListKategoriPembayarans;
use App\Filament\Resources\KategoriPembayarans\Pages\ViewKategoriPembayaran;
use App\Filament\Resources\KategoriPembayarans\Schemas\KategoriPembayaranForm;
use App\Filament\Resources\KategoriPembayarans\Schemas\KategoriPembayaranInfolist;
use App\Filament\Resources\KategoriPembayarans\Tables\KategoriPembayaransTable;
use App\Models\KategoriPembayaran;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class KategoriPembayaranResource extends Resource
{
    protected static ?string $model = KategoriPembayaran::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $navigationLabel = 'Kategori Pembayaran';

    protected static ?string $modelLabel = 'Kategori Pembayaran';

    protected static ?string $pluralModelLabel = 'Kategori Pembayaran';

    protected static UnitEnum|string|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return KategoriPembayaranForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KategoriPembayaranInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KategoriPembayaransTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKategoriPembayarans::route('/'),
            'create' => CreateKategoriPembayaran::route('/create'),
            'view' => ViewKategoriPembayaran::route('/{record}'),
            'edit' => EditKategoriPembayaran::route('/{record}/edit'),
        ];
    }
}
