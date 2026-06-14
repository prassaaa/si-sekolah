<?php

namespace App\Filament\Resources\Siswas;

use App\Filament\Resources\Siswas\Pages\CreateSiswa;
use App\Filament\Resources\Siswas\Pages\EditSiswa;
use App\Filament\Resources\Siswas\Pages\ListSiswas;
use App\Filament\Resources\Siswas\Pages\ViewSiswa;
use App\Filament\Resources\Siswas\Schemas\SiswaForm;
use App\Filament\Resources\Siswas\Schemas\SiswaInfolist;
use App\Filament\Resources\Siswas\Tables\SiswasTable;
use App\Models\Siswa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SiswaResource extends Resource
{
    protected static ?string $model = Siswa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Siswa';

    protected static ?string $modelLabel = 'Siswa';

    protected static ?string $pluralModelLabel = 'Siswa';

    protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'nama';

    /**
     * Kolom/relasi untuk pencarian global.
     *
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nama', 'nis', 'nisn'];
    }

    /**
     * Detail yang ditampilkan di hasil pencarian global.
     *
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Kelas' => $record->kelas?->nama ?? '-',
        ];
    }

    /**
     * Query dasar dengan eager-load relasi agar tidak terjadi N+1.
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('kelas');
    }

    public static function form(Schema $schema): Schema
    {
        return SiswaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SiswaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SiswasTable::configure($table);
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
            'index' => ListSiswas::route('/'),
            'create' => CreateSiswa::route('/create'),
            'view' => ViewSiswa::route('/{record}'),
            'edit' => EditSiswa::route('/{record}/edit'),
        ];
    }
}
