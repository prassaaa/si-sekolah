<?php

namespace App\Filament\Resources\JurnalUmums\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class JurnalUmumInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Jurnal')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('nomor_bukti')
                                ->label('Nomor Bukti')
                                ->copyable(),
                            TextEntry::make('tanggal')
                                ->label('Tanggal')
                                ->date('d M Y'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('akun.kode')
                                ->label('Kode Akun'),
                            TextEntry::make('akun.nama')
                                ->label('Nama Akun'),
                        ]),

                        TextEntry::make('keterangan')
                            ->label('Keterangan'),
                    ]),

                Section::make('Nominal')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('debit')
                                ->label('Debit')
                                ->money('IDR')
                                ->color(fn ($state) => $state > 0 ? 'info' : 'gray'),
                            TextEntry::make('kredit')
                                ->label('Kredit')
                                ->money('IDR')
                                ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                        ]),
                    ]),

                Section::make('Referensi')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('jenis_referensi')
                                ->label('Jenis Referensi')
                                ->badge()
                                ->placeholder('-')
                                ->formatStateUsing(fn (?string $state) => match ($state) {
                                    'pembayaran' => 'Pembayaran',
                                    'penerimaan' => 'Penerimaan',
                                    'penyesuaian' => 'Penyesuaian',
                                    'koreksi' => 'Koreksi',
                                    'lainnya' => 'Lainnya',
                                    default => '-',
                                }),
                            TextEntry::make('referensi')
                                ->label('No. Referensi')
                                ->placeholder('-'),
                        ]),

                        TextEntry::make('creator.name')
                            ->label('Dibuat Oleh'),
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
