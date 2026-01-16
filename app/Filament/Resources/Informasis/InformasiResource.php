<?php

namespace App\Filament\Resources\Informasis;

use App\Filament\Resources\Informasis\Pages\CreateInformasi;
use App\Filament\Resources\Informasis\Pages\EditInformasi;
use App\Filament\Resources\Informasis\Pages\ListInformasis;
use App\Filament\Resources\Informasis\Pages\ViewInformasi;
use App\Filament\Resources\Informasis\Schemas\InformasiForm;
use App\Filament\Resources\Informasis\Schemas\InformasiInfolist;
use App\Filament\Resources\Informasis\Tables\InformasisTable;
use App\Models\Informasi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InformasiResource extends Resource
{
    protected static ?string $model = Informasi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $navigationLabel = 'Informasi';

    protected static ?string $modelLabel = 'Informasi';

    protected static ?string $pluralModelLabel = 'Informasi';

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'judul';

    public static function getGloballySearchableAttributes(): array
    {
        return ['judul', 'ringkasan', 'konten'];
    }

    public static function form(Schema $schema): Schema
    {
        return InformasiForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InformasiInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InformasisTable::configure($table);
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
            'index' => ListInformasis::route('/'),
            'create' => CreateInformasi::route('/create'),
            'view' => ViewInformasi::route('/{record}'),
            'edit' => EditInformasi::route('/{record}/edit'),
        ];
    }
}
