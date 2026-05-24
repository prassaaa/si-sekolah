<?php

namespace App\Filament\Resources\PresensiHarianPegawais;

use App\Filament\Resources\PresensiHarianPegawais\Pages\CreatePresensiHarianPegawai;
use App\Filament\Resources\PresensiHarianPegawais\Pages\EditPresensiHarianPegawai;
use App\Filament\Resources\PresensiHarianPegawais\Pages\ListPresensiHarianPegawais;
use App\Filament\Resources\PresensiHarianPegawais\Pages\ViewPresensiHarianPegawai;
use App\Filament\Resources\PresensiHarianPegawais\Schemas\PresensiHarianPegawaiForm;
use App\Filament\Resources\PresensiHarianPegawais\Schemas\PresensiHarianPegawaiInfolist;
use App\Filament\Resources\PresensiHarianPegawais\Tables\PresensiHarianPegawaisTable;
use App\Models\PresensiHarianPegawai;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PresensiHarianPegawaiResource extends Resource
{
    protected static ?string $model = PresensiHarianPegawai::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Presensi Pegawai';

    protected static ?string $modelLabel = 'Presensi Pegawai';

    protected static ?string $pluralModelLabel = 'Presensi Pegawai';

    protected static UnitEnum|string|null $navigationGroup = 'Kepegawaian';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'pegawai.nama';

    public static function getGloballySearchableAttributes(): array
    {
        return ['pegawai.nama', 'pegawai.nip'];
    }

    public static function form(Schema $schema): Schema
    {
        return PresensiHarianPegawaiForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PresensiHarianPegawaiInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PresensiHarianPegawaisTable::configure($table);
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
            'index' => ListPresensiHarianPegawais::route('/'),
            'create' => CreatePresensiHarianPegawai::route('/create'),
            'view' => ViewPresensiHarianPegawai::route('/{record}'),
            'edit' => EditPresensiHarianPegawai::route('/{record}/edit'),
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
