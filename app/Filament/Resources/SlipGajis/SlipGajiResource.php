<?php

namespace App\Filament\Resources\SlipGajis;

use App\Filament\Resources\SlipGajis\Pages\CreateSlipGaji;
use App\Filament\Resources\SlipGajis\Pages\EditSlipGaji;
use App\Filament\Resources\SlipGajis\Pages\ListSlipGajis;
use App\Filament\Resources\SlipGajis\Schemas\SlipGajiForm;
use App\Filament\Resources\SlipGajis\Tables\SlipGajisTable;
use App\Models\SlipGaji;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SlipGajiResource extends Resource
{
    protected static ?string $model = SlipGaji::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static UnitEnum|string|null $navigationGroup = 'Penggajian';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Slip Gaji';

    protected static ?string $modelLabel = 'Slip Gaji';

    protected static ?string $pluralModelLabel = 'Slip Gaji';

    public static function form(Schema $schema): Schema
    {
        return SlipGajiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SlipGajisTable::configure($table);
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
            'index' => ListSlipGajis::route('/'),
            'create' => CreateSlipGaji::route('/create'),
            'edit' => EditSlipGaji::route('/{record}/edit'),
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
