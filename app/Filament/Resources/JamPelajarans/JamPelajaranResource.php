<?php

namespace App\Filament\Resources\JamPelajarans;

use App\Filament\Resources\JamPelajarans\Pages\CreateJamPelajaran;
use App\Filament\Resources\JamPelajarans\Pages\EditJamPelajaran;
use App\Filament\Resources\JamPelajarans\Pages\ListJamPelajarans;
use App\Filament\Resources\JamPelajarans\Pages\ViewJamPelajaran;
use App\Filament\Resources\JamPelajarans\Schemas\JamPelajaranForm;
use App\Filament\Resources\JamPelajarans\Schemas\JamPelajaranInfolist;
use App\Filament\Resources\JamPelajarans\Tables\JamPelajaransTable;
use App\Models\JamPelajaran;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class JamPelajaranResource extends Resource
{
    protected static ?string $model = JamPelajaran::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Jam Pelajaran';

    protected static ?string $modelLabel = 'Jam Pelajaran';

    protected static ?string $pluralModelLabel = 'Jam Pelajaran';

    protected static UnitEnum|string|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'label';

    /**
     * 'label' adalah accessor (bukan kolom DB) — pakai kolom nyata untuk
     * pencarian global agar tidak menabrak WHERE ke kolom tak ada.
     *
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['jam_ke', 'jenis', 'keterangan'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->label;
    }

    public static function form(Schema $schema): Schema
    {
        return JamPelajaranForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return JamPelajaranInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JamPelajaransTable::configure($table);
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
            'index' => ListJamPelajarans::route('/'),
            'create' => CreateJamPelajaran::route('/create'),
            'view' => ViewJamPelajaran::route('/{record}'),
            'edit' => EditJamPelajaran::route('/{record}/edit'),
        ];
    }
}
