<?php

namespace App\Filament\Resources\Prestasis;

use App\Filament\Resources\Prestasis\Pages\CreatePrestasi;
use App\Filament\Resources\Prestasis\Pages\EditPrestasi;
use App\Filament\Resources\Prestasis\Pages\ListPrestasis;
use App\Filament\Resources\Prestasis\Pages\ViewPrestasi;
use App\Filament\Resources\Prestasis\Schemas\PrestasiForm;
use App\Filament\Resources\Prestasis\Schemas\PrestasiInfolist;
use App\Filament\Resources\Prestasis\Tables\PrestasisTable;
use App\Models\Prestasi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PrestasiResource extends Resource
{
    protected static ?string $model = Prestasi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Prestasi';

    protected static ?string $modelLabel = 'Prestasi';

    protected static ?string $pluralModelLabel = 'Prestasi';

    protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'nama_prestasi';

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama_prestasi', 'siswa.nama', 'siswa.nis'];
    }

    /** @return array<string, string> */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Siswa' => $record->siswa?->nama ?? '-',
            'Kelas' => $record->siswa?->kelas?->nama ?? '-',
        ];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('siswa.kelas');
    }

    public static function form(Schema $schema): Schema
    {
        return PrestasiForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PrestasiInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrestasisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrestasis::route('/'),
            'create' => CreatePrestasi::route('/create'),
            'view' => ViewPrestasi::route('/{record}'),
            'edit' => EditPrestasi::route('/{record}/edit'),
        ];
    }
}
