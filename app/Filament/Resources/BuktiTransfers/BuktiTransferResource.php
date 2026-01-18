<?php

namespace App\Filament\Resources\BuktiTransfers;

use App\Filament\Resources\BuktiTransfers\Pages\CreateBuktiTransfer;
use App\Filament\Resources\BuktiTransfers\Pages\EditBuktiTransfer;
use App\Filament\Resources\BuktiTransfers\Pages\ListBuktiTransfers;
use App\Filament\Resources\BuktiTransfers\Schemas\BuktiTransferForm;
use App\Filament\Resources\BuktiTransfers\Tables\BuktiTransfersTable;
use App\Models\BuktiTransfer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class BuktiTransferResource extends Resource
{
    protected static ?string $model = BuktiTransfer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static UnitEnum|string|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Bukti Transfer';

    protected static ?string $pluralModelLabel = 'Bukti Transfer';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() > 0 ? 'warning' : 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return BuktiTransferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BuktiTransfersTable::configure($table);
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
            'index' => ListBuktiTransfers::route('/'),
            'create' => CreateBuktiTransfer::route('/create'),
            'edit' => EditBuktiTransfer::route('/{record}/edit'),
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
