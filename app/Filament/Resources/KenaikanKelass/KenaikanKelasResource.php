<?php

namespace App\Filament\Resources\KenaikanKelass;

use App\Filament\Resources\KenaikanKelass\Pages\CreateKenaikanKelas;
use App\Filament\Resources\KenaikanKelass\Pages\EditKenaikanKelas;
use App\Filament\Resources\KenaikanKelass\Pages\ListKenaikanKelass;
use App\Filament\Resources\KenaikanKelass\Pages\ViewKenaikanKelas;
use App\Filament\Resources\KenaikanKelass\Schemas\KenaikanKelasForm;
use App\Filament\Resources\KenaikanKelass\Schemas\KenaikanKelasInfolist;
use App\Filament\Resources\KenaikanKelass\Tables\KenaikanKelassTable;
use App\Models\KenaikanKelas;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class KenaikanKelasResource extends Resource
{
    protected static ?string $model = KenaikanKelas::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static ?string $navigationLabel = 'Kenaikan Kelas';

    protected static ?string $modelLabel = 'Kenaikan Kelas';

    protected static ?string $pluralModelLabel = 'Kenaikan Kelas';

    protected static UnitEnum|string|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return KenaikanKelasForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KenaikanKelasInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KenaikanKelassTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKenaikanKelass::route('/'),
            'create' => CreateKenaikanKelas::route('/create'),
            'view' => ViewKenaikanKelas::route('/{record}'),
            'edit' => EditKenaikanKelas::route('/{record}/edit'),
        ];
    }
}
