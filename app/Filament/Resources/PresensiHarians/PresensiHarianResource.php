<?php

namespace App\Filament\Resources\PresensiHarians;

use App\Filament\Resources\PresensiHarians\Pages\CreatePresensiHarian;
use App\Filament\Resources\PresensiHarians\Pages\EditPresensiHarian;
use App\Filament\Resources\PresensiHarians\Pages\ListPresensiHarians;
use App\Filament\Resources\PresensiHarians\Pages\ViewPresensiHarian;
use App\Filament\Resources\PresensiHarians\Schemas\PresensiHarianForm;
use App\Filament\Resources\PresensiHarians\Schemas\PresensiHarianInfolist;
use App\Filament\Resources\PresensiHarians\Tables\PresensiHariansTable;
use App\Models\PresensiHarian;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PresensiHarianResource extends Resource
{
    protected static ?string $model = PresensiHarian::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Presensi Harian';

    protected static ?string $modelLabel = 'Presensi Harian';

    protected static ?string $pluralModelLabel = 'Presensi Harian';

    protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'siswa.nama';

    public static function getGloballySearchableAttributes(): array
    {
        return ['siswa.nama', 'siswa.nis'];
    }

    public static function form(Schema $schema): Schema
    {
        return PresensiHarianForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PresensiHarianInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PresensiHariansTable::configure($table);
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
            'index' => ListPresensiHarians::route('/'),
            'create' => CreatePresensiHarian::route('/create'),
            'view' => ViewPresensiHarian::route('/{record}'),
            'edit' => EditPresensiHarian::route('/{record}/edit'),
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
