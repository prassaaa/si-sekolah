<?php

namespace App\Filament\Resources\Tahfidzs;

use App\Filament\Resources\Tahfidzs\Pages\CreateTahfidz;
use App\Filament\Resources\Tahfidzs\Pages\EditTahfidz;
use App\Filament\Resources\Tahfidzs\Pages\ListTahfidzs;
use App\Filament\Resources\Tahfidzs\Pages\ViewTahfidz;
use App\Filament\Resources\Tahfidzs\Schemas\TahfidzForm;
use App\Filament\Resources\Tahfidzs\Schemas\TahfidzInfolist;
use App\Filament\Resources\Tahfidzs\Tables\TahfidzsTable;
use App\Models\Tahfidz;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TahfidzResource extends Resource
{
    protected static ?string $model = Tahfidz::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'Tahfidz';

    protected static ?string $modelLabel = 'Tahfidz';

    protected static ?string $pluralModelLabel = 'Tahfidz';

    protected static UnitEnum|string|null $navigationGroup = 'Kesiswaan';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'surah';

    public static function form(Schema $schema): Schema
    {
        return TahfidzForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TahfidzInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TahfidzsTable::configure($table);
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
            'index' => ListTahfidzs::route('/'),
            'create' => CreateTahfidz::route('/create'),
            'view' => ViewTahfidz::route('/{record}'),
            'edit' => EditTahfidz::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
