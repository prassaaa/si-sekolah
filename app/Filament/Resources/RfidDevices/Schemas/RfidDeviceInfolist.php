<?php

namespace App\Filament\Resources\RfidDevices\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RfidDeviceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Device')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('nama')->label('Nama'),
                    TextEntry::make('kode')->label('Kode')->copyable(),
                    TextEntry::make('jenis')
                        ->label('Jenis')
                        ->badge()
                        ->color(fn ($record) => $record->jenis_info['color'])
                        ->formatStateUsing(fn ($record) => $record->jenis_info['label']),
                    TextEntry::make('lokasi')->label('Lokasi')->placeholder('-'),
                ]),
            ]),

            Section::make('Status & Aktivitas')->schema([
                Grid::make(2)->schema([
                    IconEntry::make('is_active')
                        ->label('Aktif')
                        ->boolean(),
                    TextEntry::make('terakhir_aktif')
                        ->label('Terakhir Aktif')
                        ->dateTime('d M Y H:i:s')
                        ->placeholder('Belum pernah aktif'),
                ]),
                TextEntry::make('keterangan')->label('Keterangan')->placeholder('-')->columnSpanFull(),
            ]),
        ]);
    }
}
