<?php

namespace App\Filament\Resources\MutasiBanks;

use App\Filament\Resources\MutasiBanks\Pages\CreateMutasiBank;
use App\Filament\Resources\MutasiBanks\Pages\EditMutasiBank;
use App\Filament\Resources\MutasiBanks\Pages\ListMutasiBanks;
use App\Filament\Resources\MutasiBanks\Schemas\MutasiBankForm;
use App\Filament\Resources\MutasiBanks\Tables\MutasiBanksTable;
use App\Models\Akun;
use App\Models\MutasiBank;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class MutasiBankResource extends Resource
{
    protected static ?string $model = MutasiBank::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Kas & Bank';

    protected static ?string $navigationLabel = 'Mutasi Bank';

    protected static ?string $modelLabel = 'Mutasi Bank';

    protected static ?string $pluralModelLabel = 'Mutasi Bank';

    protected static ?int $navigationSort = 11;

    protected static ?string $recordTitleAttribute = 'keterangan';

    public static function form(Schema $schema): Schema
    {
        return MutasiBankForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MutasiBanksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMutasiBanks::route('/'),
            'create' => CreateMutasiBank::route('/create'),
            'edit' => EditMutasiBank::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * Pilihan akun bank (akun aset lancar yang namanya mengandung "Bank") untuk
     * dropdown input mutasi rekening koran.
     *
     * @return array<int, string>
     */
    public static function bankAkunOptions(): array
    {
        return Akun::query()
            ->where('tipe', 'aset')
            ->where('kategori', 'lancar')
            ->where('nama', 'like', '%Bank%')
            ->orderBy('kode')
            ->get()
            ->mapWithKeys(fn (Akun $akun): array => [$akun->id => "{$akun->kode} - {$akun->nama}"])
            ->all();
    }
}
