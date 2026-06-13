<?php

namespace App\Filament\Resources\Anggarans;

use App\Filament\Resources\Anggarans\Pages\CreateAnggaran;
use App\Filament\Resources\Anggarans\Pages\EditAnggaran;
use App\Filament\Resources\Anggarans\Pages\ListAnggarans;
use App\Filament\Resources\Anggarans\Schemas\AnggaranForm;
use App\Filament\Resources\Anggarans\Tables\AnggaransTable;
use App\Models\Anggaran;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class AnggaranResource extends Resource
{
    protected static ?string $model = Anggaran::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Anggaran RAPBS';

    protected static ?string $modelLabel = 'Anggaran';

    protected static ?string $pluralModelLabel = 'Anggaran RAPBS';

    protected static UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'keterangan';

    public static function form(Schema $schema): Schema
    {
        return AnggaranForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnggaransTable::configure($table);
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
            'index' => ListAnggarans::route('/'),
            'create' => CreateAnggaran::route('/create'),
            'edit' => EditAnggaran::route('/{record}/edit'),
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
