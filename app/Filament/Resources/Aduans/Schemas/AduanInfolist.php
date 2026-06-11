<?php

namespace App\Filament\Resources\Aduans\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AduanInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pelapor')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('pelapor')
                                ->label('Nama Pelapor'),

                            TextEntry::make('hubungan_pelapor')
                                ->label('Hubungan')
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'siswa' => 'Siswa',
                                    'ayah' => 'Ayah',
                                    'ibu' => 'Ibu',
                                    'wali' => 'Wali',
                                    default => 'Lainnya',
                                }),

                            TextEntry::make('kontak_pelapor')
                                ->label('Kontak')
                                ->placeholder('-'),
                        ]),

                        Grid::make(2)->schema([
                            TextEntry::make('siswa.nama')
                                ->label('Siswa Terkait')
                                ->placeholder('-'),

                            TextEntry::make('tanggal_aduan')
                                ->label('Tanggal Aduan')
                                ->date('d M Y'),
                        ]),
                    ]),

                Section::make('Isi Aduan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('kategori')
                                ->label('Kategori')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'akademik' => 'Akademik',
                                    'fasilitas' => 'Fasilitas',
                                    'perlakuan' => 'Perlakuan',
                                    'keuangan' => 'Keuangan',
                                    default => 'Lainnya',
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'akademik' => 'info',
                                    'fasilitas' => 'warning',
                                    'perlakuan' => 'danger',
                                    'keuangan' => 'success',
                                    default => 'gray',
                                }),

                            TextEntry::make('judul')
                                ->label('Judul Aduan'),
                        ]),

                        TextEntry::make('isi')
                            ->label('Isi Aduan')
                            ->columnSpanFull(),

                        TextEntry::make('lampiran')
                            ->label('Lampiran')
                            ->placeholder('-'),
                    ]),

                Section::make('Penanganan')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('status')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn (string $state) => match ($state) {
                                    'baru' => 'Baru',
                                    'diproses' => 'Diproses',
                                    'selesai' => 'Selesai',
                                    'ditolak' => 'Ditolak',
                                    default => $state,
                                })
                                ->color(fn (string $state) => match ($state) {
                                    'baru' => 'danger',
                                    'diproses' => 'warning',
                                    'selesai' => 'success',
                                    'ditolak' => 'gray',
                                    default => 'gray',
                                }),

                            TextEntry::make('penangan.nama')
                                ->label('Ditangani Oleh')
                                ->placeholder('-'),
                        ]),

                        TextEntry::make('tanggapan')
                            ->label('Tanggapan')
                            ->placeholder('-')
                            ->columnSpanFull(),

                        TextEntry::make('tanggal_tanggapan')
                            ->label('Tanggal Tanggapan')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
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
