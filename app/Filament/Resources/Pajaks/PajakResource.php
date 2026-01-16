<?php

namespace App\Filament\Resources\Pajaks;

use App\Filament\Resources\Pajaks\Pages\CreatePajak;
use App\Filament\Resources\Pajaks\Pages\EditPajak;
use App\Filament\Resources\Pajaks\Pages\ListPajaks;
use App\Filament\Resources\Pajaks\Schemas\PajakForm;
use App\Filament\Resources\Pajaks\Tables\PajaksTable;
use App\Models\Pajak;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PajakResource extends Resource
{
    protected static ?string $model = Pajak::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static UnitEnum|string|null $navigationGroup = 'Setting Pembayaran';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Pajak';

    protected static ?string $pluralModelLabel = 'Pajak';

    public static function form(Schema $schema): Schema
    {
        return PajakForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PajaksTable::configure($table);
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
            'index' => ListPajaks::route('/'),
            'create' => CreatePajak::route('/create'),
            'edit' => EditPajak::route('/{record}/edit'),
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
