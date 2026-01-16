<?php

namespace App\Filament\Resources\KategoriPembayarans\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class KategoriPembayaranInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Kategori')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('kode')
                                ->label('Kode'),
                            TextEntry::make('nama')
                                ->label('Nama Kategori'),
                        ]),

                        TextEntry::make('deskripsi')
                            ->label('Deskripsi')
                            ->placeholder('-'),

                        Grid::make(2)->schema([
                            TextEntry::make('urutan')
                                ->label('Urutan'),
                            IconEntry::make('is_active')
                                ->label('Aktif')
                                ->boolean(),
                        ]),
                    ]),

                Section::make('Informasi Sistem')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')
                                ->label('Dibuat')
                                ->dateTime('d M Y H:i'),
                            TextEntry::make('updated_at')
                                ->label('Diperbarui')
                                ->dateTime('d M Y H:i'),
                        ]),
                    ])
                    ->collapsed(),
            ]);
    }
}
