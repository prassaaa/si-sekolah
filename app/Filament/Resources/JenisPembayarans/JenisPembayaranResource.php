<?php

namespace App\Filament\Resources\JenisPembayarans;

use App\Filament\Resources\JenisPembayarans\Pages\CreateJenisPembayaran;
use App\Filament\Resources\JenisPembayarans\Pages\EditJenisPembayaran;
use App\Filament\Resources\JenisPembayarans\Pages\ListJenisPembayarans;
use App\Filament\Resources\JenisPembayarans\Pages\ViewJenisPembayaran;
use App\Filament\Resources\JenisPembayarans\Schemas\JenisPembayaranForm;
use App\Filament\Resources\JenisPembayarans\Schemas\JenisPembayaranInfolist;
use App\Filament\Resources\JenisPembayarans\Tables\JenisPembayaransTable;
use App\Models\JenisPembayaran;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class JenisPembayaranResource extends Resource
{
    protected static ?string $model = JenisPembayaran::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Jenis Pembayaran';

    protected static ?string $modelLabel = 'Jenis Pembayaran';

    protected static ?string $pluralModelLabel = 'Jenis Pembayaran';

    protected static UnitEnum|string|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return JenisPembayaranForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return JenisPembayaranInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JenisPembayaransTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJenisPembayarans::route('/'),
            'create' => CreateJenisPembayaran::route('/create'),
            'view' => ViewJenisPembayaran::route('/{record}'),
            'edit' => EditJenisPembayaran::route('/{record}/edit'),
        ];
    }
}
