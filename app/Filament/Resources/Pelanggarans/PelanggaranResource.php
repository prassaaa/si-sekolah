<?php

namespace App\Filament\Resources\Pelanggarans;

use App\Filament\Resources\Pelanggarans\Pages\CreatePelanggaran;
use App\Filament\Resources\Pelanggarans\Pages\EditPelanggaran;
use App\Filament\Resources\Pelanggarans\Pages\ListPelanggarans;
use App\Filament\Resources\Pelanggarans\Pages\ViewPelanggaran;
use App\Filament\Resources\Pelanggarans\Schemas\PelanggaranForm;
use App\Filament\Resources\Pelanggarans\Schemas\PelanggaranInfolist;
use App\Filament\Resources\Pelanggarans\Tables\PelanggaransTable;
use App\Models\Pelanggaran;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PelanggaranResource extends Resource
{
    protected static ?string $model = Pelanggaran::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Pelanggaran';

    protected static ?string $modelLabel = 'Pelanggaran';

    protected static ?string $pluralModelLabel = 'Pelanggaran';

    protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'jenis_pelanggaran';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() > 0 ? 'danger' : 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return PelanggaranForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PelanggaranInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PelanggaransTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPelanggarans::route('/'),
            'create' => CreatePelanggaran::route('/create'),
            'view' => ViewPelanggaran::route('/{record}'),
            'edit' => EditPelanggaran::route('/{record}/edit'),
        ];
    }
}
