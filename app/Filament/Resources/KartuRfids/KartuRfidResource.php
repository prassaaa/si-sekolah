<?php

namespace App\Filament\Resources\KartuRfids;

use App\Filament\Resources\KartuRfids\Pages\CreateKartuRfid;
use App\Filament\Resources\KartuRfids\Pages\EditKartuRfid;
use App\Filament\Resources\KartuRfids\Pages\ListKartuRfids;
use App\Filament\Resources\KartuRfids\Pages\ViewKartuRfid;
use App\Filament\Resources\KartuRfids\Schemas\KartuRfidForm;
use App\Filament\Resources\KartuRfids\Schemas\KartuRfidInfolist;
use App\Filament\Resources\KartuRfids\Tables\KartuRfidsTable;
use App\Models\KartuRfid;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class KartuRfidResource extends Resource
{
    protected static ?string $model = KartuRfid::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $navigationLabel = 'Kartu RFID';

    protected static ?string $modelLabel = 'Kartu RFID';

    protected static ?string $pluralModelLabel = 'Kartu RFID';

    protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?int $navigationSort = 110;

    protected static ?string $recordTitleAttribute = 'uid';

    public static function getGloballySearchableAttributes(): array
    {
        return ['uid', 'siswa.nama', 'siswa.nis'];
    }

    public static function form(Schema $schema): Schema
    {
        return KartuRfidForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KartuRfidInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KartuRfidsTable::configure($table);
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
            'index' => ListKartuRfids::route('/'),
            'create' => CreateKartuRfid::route('/create'),
            'view' => ViewKartuRfid::route('/{record}'),
            'edit' => EditKartuRfid::route('/{record}/edit'),
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
