<?php

namespace App\Filament\Resources\KartuRfids\Schemas;

use App\Models\Pegawai;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KartuRfidInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Kartu')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('uid')->label('UID')->copyable(),
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($record) => $record->status_info['color'])
                        ->formatStateUsing(fn ($record) => $record->status_info['label']),
                ]),
            ]),

            Section::make('Pemilik')->schema([
                TextEntry::make('owner_type')
                    ->label('Tipe Pemilik')
                    ->formatStateUsing(fn ($state) => $state === Pegawai::class ? 'Pegawai' : 'Siswa'),
                TextEntry::make('owner.nama')->label('Nama'),
                TextEntry::make('owner.nis')->label('NIS')->visible(fn ($record) => $record->owner_type !== Pegawai::class),
                TextEntry::make('owner.nip')->label('NIP')->visible(fn ($record) => $record->owner_type === Pegawai::class),
            ]),

            Section::make('Riwayat Status')->schema([
                Grid::make(2)->schema([
                    TextEntry::make('diaktifkan_pada')->label('Diaktifkan')->dateTime('d M Y H:i'),
                    TextEntry::make('dinonaktifkan_pada')->label('Dinonaktifkan')->dateTime('d M Y H:i')->placeholder('-'),
                ]),
                TextEntry::make('keterangan')->label('Keterangan')->placeholder('-')->columnSpanFull(),
            ]),
        ]);
    }
}
