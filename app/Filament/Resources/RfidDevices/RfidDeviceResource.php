<?php

namespace App\Filament\Resources\RfidDevices;

use App\Filament\Resources\RfidDevices\Pages\CreateRfidDevice;
use App\Filament\Resources\RfidDevices\Pages\EditRfidDevice;
use App\Filament\Resources\RfidDevices\Pages\ListRfidDevices;
use App\Filament\Resources\RfidDevices\Pages\ViewRfidDevice;
use App\Filament\Resources\RfidDevices\Schemas\RfidDeviceForm;
use App\Filament\Resources\RfidDevices\Schemas\RfidDeviceInfolist;
use App\Filament\Resources\RfidDevices\Tables\RfidDevicesTable;
use App\Models\RfidDevice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RfidDeviceResource extends Resource
{
    protected static ?string $model = RfidDevice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $navigationLabel = 'RFID Device';

    protected static ?string $modelLabel = 'RFID Device';

    protected static ?string $pluralModelLabel = 'RFID Devices';

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return RfidDeviceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RfidDeviceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RfidDevicesTable::configure($table);
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
            'index' => ListRfidDevices::route('/'),
            'create' => CreateRfidDevice::route('/create'),
            'view' => ViewRfidDevice::route('/{record}'),
            'edit' => EditRfidDevice::route('/{record}/edit'),
        ];
    }
}
