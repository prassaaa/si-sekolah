<?php

namespace App\Filament\Resources\TagihanSiswas;

use App\Filament\Resources\TagihanSiswas\Pages\CreateTagihanSiswa;
use App\Filament\Resources\TagihanSiswas\Pages\EditTagihanSiswa;
use App\Filament\Resources\TagihanSiswas\Pages\ListTagihanSiswas;
use App\Filament\Resources\TagihanSiswas\Pages\ViewTagihanSiswa;
use App\Filament\Resources\TagihanSiswas\Schemas\TagihanSiswaForm;
use App\Filament\Resources\TagihanSiswas\Schemas\TagihanSiswaInfolist;
use App\Filament\Resources\TagihanSiswas\Tables\TagihanSiswasTable;
use App\Models\TagihanSiswa;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TagihanSiswaResource extends Resource
{
    protected static ?string $model = TagihanSiswa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Tagihan Siswa';

    protected static ?string $modelLabel = 'Tagihan Siswa';

    protected static ?string $pluralModelLabel = 'Tagihan Siswa';

    protected static UnitEnum|string|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'nomor_tagihan';

    /**
     * Kolom/relasi untuk pencarian global.
     *
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nomor_tagihan', 'siswa.nama', 'siswa.nis'];
    }

    /**
     * Detail yang ditampilkan di hasil pencarian global.
     *
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Siswa' => $record->siswa?->nama ?? '-',
            'Kelas' => $record->siswa?->kelas?->nama ?? '-',
        ];
    }

    /**
     * Query dasar dengan eager-load relasi agar tidak terjadi N+1.
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('siswa.kelas');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['belum_bayar', 'sebagian'])
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = (int) static::getNavigationBadge();

        return $count > 10 ? 'danger' : ($count > 0 ? 'warning' : 'primary');
    }

    public static function form(Schema $schema): Schema
    {
        return TagihanSiswaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TagihanSiswaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TagihanSiswasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTagihanSiswas::route('/'),
            'create' => CreateTagihanSiswa::route('/create'),
            'view' => ViewTagihanSiswa::route('/{record}'),
            'edit' => EditTagihanSiswa::route('/{record}/edit'),
        ];
    }
}
