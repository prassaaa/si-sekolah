<?php

namespace App\Filament\Resources\Akuns\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AkunInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Akun')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('kode')
                                ->label('Kode Akun'),
                            TextEntry::make('nama')
                                ->label('Nama Akun'),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('tipe')
                                ->label('Tipe')
                                ->badge()
                                ->color(fn (string $state) => match ($state) {
                                    'aset' => 'info',
                                    'liabilitas' => 'warning',
                                    'ekuitas' => 'success',
                                    'pendapatan' => 'primary',
                                    'beban' => 'danger',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (string $state) => ucfirst($state)),
                            TextEntry::make('kategori')
                                ->label('Kategori')
                                ->formatStateUsing(fn (?string $state) => match ($state) {
                                    'lancar' => 'Lancar',
                                    'tetap' => 'Tetap',
                                    'jangka_panjang' => 'Jangka Panjang',
                                    'operasional' => 'Operasional',
                                    'non_operasional' => 'Non Operasional',
                                    default => '-',
                                }),
                            TextEntry::make('posisi_normal')
                                ->label('Posisi Normal')
                                ->badge()
                                ->color(fn (string $state) => $state === 'debit' ? 'info' : 'warning')
                                ->formatStateUsing(fn (string $state) => ucfirst($state)),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('parent.nama')
                                ->label('Parent Akun')
                                ->placeholder('-'),
                            TextEntry::make('level')
                                ->label('Level'),
                        ]),
                    ]),

                Section::make('Saldo')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('saldo_awal')
                                ->label('Saldo Awal')
                                ->money('IDR'),
                            TextEntry::make('saldo_akhir')
                                ->label('Saldo Akhir')
                                ->money('IDR')
                                ->weight('bold'),
                        ]),

                        Grid::make(2)->schema([
                            IconEntry::make('is_active')
                                ->label('Status Aktif')
                                ->boolean(),
                            TextEntry::make('deskripsi')
                                ->label('Deskripsi')
                                ->placeholder('-'),
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
