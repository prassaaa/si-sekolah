<?php

namespace App\Filament\Resources\Sekolahs;

use App\Filament\Resources\Sekolahs\Pages\CreateSekolah;
use App\Filament\Resources\Sekolahs\Pages\EditSekolah;
use App\Filament\Resources\Sekolahs\Pages\ListSekolahs;
use App\Filament\Resources\Sekolahs\Pages\ViewSekolah;
use App\Filament\Resources\Sekolahs\Schemas\SekolahForm;
use App\Filament\Resources\Sekolahs\Schemas\SekolahInfolist;
use App\Filament\Resources\Sekolahs\Tables\SekolahsTable;
use App\Models\Sekolah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SekolahResource extends Resource
{
    protected static ?string $model = Sekolah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'Sekolah';

    protected static ?string $modelLabel = 'Sekolah';

    protected static ?string $pluralModelLabel = 'Sekolah';

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function getGloballySearchableAttributes(): array
    {
        return ['npsn', 'nama', 'kepala_sekolah'];
    }

    public static function form(Schema $schema): Schema
    {
        return SekolahForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SekolahInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SekolahsTable::configure($table);
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
            'index' => ListSekolahs::route('/'),
            'create' => CreateSekolah::route('/create'),
            'view' => ViewSekolah::route('/{record}'),
            'edit' => EditSekolah::route('/{record}/edit'),
        ];
    }
}
