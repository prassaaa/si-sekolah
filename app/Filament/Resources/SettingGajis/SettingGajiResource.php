<?php

namespace App\Filament\Resources\SettingGajis;

use App\Filament\Resources\SettingGajis\Pages\CreateSettingGaji;
use App\Filament\Resources\SettingGajis\Pages\EditSettingGaji;
use App\Filament\Resources\SettingGajis\Pages\ListSettingGajis;
use App\Filament\Resources\SettingGajis\Schemas\SettingGajiForm;
use App\Filament\Resources\SettingGajis\Tables\SettingGajisTable;
use App\Models\SettingGaji;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SettingGajiResource extends Resource
{
    protected static ?string $model = SettingGaji::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog;

    protected static UnitEnum|string|null $navigationGroup = 'Penggajian';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Setting Gaji';

    protected static ?string $modelLabel = 'Setting Gaji';

    protected static ?string $pluralModelLabel = 'Setting Gaji';

    public static function form(Schema $schema): Schema
    {
        return SettingGajiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettingGajisTable::configure($table);
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
            'index' => ListSettingGajis::route('/'),
            'create' => CreateSettingGaji::route('/create'),
            'edit' => EditSettingGaji::route('/{record}/edit'),
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
