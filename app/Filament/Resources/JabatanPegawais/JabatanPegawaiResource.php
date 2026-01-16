<?php

namespace App\Filament\Resources\JabatanPegawais;

use App\Filament\Resources\JabatanPegawais\Pages\CreateJabatanPegawai;
use App\Filament\Resources\JabatanPegawais\Pages\EditJabatanPegawai;
use App\Filament\Resources\JabatanPegawais\Pages\ListJabatanPegawais;
use App\Filament\Resources\JabatanPegawais\Pages\ViewJabatanPegawai;
use App\Filament\Resources\JabatanPegawais\Schemas\JabatanPegawaiForm;
use App\Filament\Resources\JabatanPegawais\Schemas\JabatanPegawaiInfolist;
use App\Filament\Resources\JabatanPegawais\Tables\JabatanPegawaisTable;
use App\Models\JabatanPegawai;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class JabatanPegawaiResource extends Resource
{
    protected static ?string $model = JabatanPegawai::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?string $navigationLabel = 'Jabatan Pegawai';

    protected static ?string $modelLabel = 'Jabatan Pegawai';

    protected static ?string $pluralModelLabel = 'Jabatan Pegawai';

    protected static UnitEnum|string|null $navigationGroup = 'Kepegawaian';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function getGloballySearchableAttributes(): array
    {
        return ['kode', 'nama'];
    }

    public static function form(Schema $schema): Schema
    {
        return JabatanPegawaiForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return JabatanPegawaiInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JabatanPegawaisTable::configure($table);
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
            'index' => ListJabatanPegawais::route('/'),
            'create' => CreateJabatanPegawai::route('/create'),
            'view' => ViewJabatanPegawai::route('/{record}'),
            'edit' => EditJabatanPegawai::route('/{record}/edit'),
        ];
    }
}
