<?php

namespace App\Filament\Resources\PeriodeAkuntansis;

use App\Filament\Resources\PeriodeAkuntansis\Pages\CreatePeriodeAkuntansi;
use App\Filament\Resources\PeriodeAkuntansis\Pages\ListPeriodeAkuntansis;
use App\Filament\Resources\PeriodeAkuntansis\Schemas\PeriodeAkuntansiForm;
use App\Filament\Resources\PeriodeAkuntansis\Tables\PeriodeAkuntansisTable;
use App\Models\PeriodeAkuntansi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PeriodeAkuntansiResource extends Resource
{
    protected static ?string $model = PeriodeAkuntansi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static ?string $navigationLabel = 'Tutup Buku';

    protected static ?string $modelLabel = 'Periode Akuntansi';

    protected static ?string $pluralModelLabel = 'Periode Akuntansi';

    protected static UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 130;

    public static function form(Schema $schema): Schema
    {
        return PeriodeAkuntansiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PeriodeAkuntansisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPeriodeAkuntansis::route('/'),
            'create' => CreatePeriodeAkuntansi::route('/create'),
        ];
    }
}
