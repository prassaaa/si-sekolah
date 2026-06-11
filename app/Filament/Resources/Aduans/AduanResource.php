<?php

namespace App\Filament\Resources\Aduans;

use App\Filament\Resources\Aduans\Pages\CreateAduan;
use App\Filament\Resources\Aduans\Pages\EditAduan;
use App\Filament\Resources\Aduans\Pages\ListAduans;
use App\Filament\Resources\Aduans\Pages\ViewAduan;
use App\Filament\Resources\Aduans\Schemas\AduanForm;
use App\Filament\Resources\Aduans\Schemas\AduanInfolist;
use App\Filament\Resources\Aduans\Tables\AduansTable;
use App\Models\Aduan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AduanResource extends Resource
{
    protected static ?string $model = Aduan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Aduan';

    protected static ?string $modelLabel = 'Aduan';

    protected static ?string $pluralModelLabel = 'Aduan';

    protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'judul';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'baru')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'baru')->count() > 0 ? 'danger' : 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return AduanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AduanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AduansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAduans::route('/'),
            'create' => CreateAduan::route('/create'),
            'view' => ViewAduan::route('/{record}'),
            'edit' => EditAduan::route('/{record}/edit'),
        ];
    }
}
