<?php

namespace App\Filament\Resources\RfidScanLogs;

use App\Filament\Resources\RfidScanLogs\Pages\ListRfidScanLogs;
use App\Filament\Resources\RfidScanLogs\Pages\ViewRfidScanLog;
use App\Filament\Resources\RfidScanLogs\Schemas\RfidScanLogForm;
use App\Filament\Resources\RfidScanLogs\Schemas\RfidScanLogInfolist;
use App\Filament\Resources\RfidScanLogs\Tables\RfidScanLogsTable;
use App\Models\RfidScanLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RfidScanLogResource extends Resource
{
    protected static ?string $model = RfidScanLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static ?string $navigationLabel = 'Log Scan RFID';

    protected static ?string $modelLabel = 'Log Scan RFID';

    protected static ?string $pluralModelLabel = 'Log Scan RFID';

    protected static UnitEnum|string|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return RfidScanLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RfidScanLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RfidScanLogsTable::configure($table);
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
            'index' => ListRfidScanLogs::route('/'),
            'view' => ViewRfidScanLog::route('/{record}'),
        ];
    }
}
