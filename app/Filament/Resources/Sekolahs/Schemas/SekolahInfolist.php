<?php

namespace App\Filament\Resources\Sekolahs\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SekolahInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Sekolah')
                    ->schema([
                        TextEntry::make('npsn')
                            ->label('NPSN')
                            ->badge(),
                        TextEntry::make('nama')
                            ->label('Nama Sekolah'),
                        TextEntry::make('nama_yayasan')
                            ->label('Nama Yayasan')
                            ->default('-'),
                        TextEntry::make('jenjang')
                            ->label('Jenjang')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Negeri' => 'success',
                                'Swasta' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('tahun_berdiri')
                            ->label('Tahun Berdiri')
                            ->default('-'),
                    ])
                    ->columns(3),

                Section::make('Alamat')
                    ->schema([
                        TextEntry::make('alamat')
                            ->label('Alamat Lengkap')
                            ->columnSpanFull(),
                        TextEntry::make('kelurahan')
                            ->label('Kelurahan/Desa')
                            ->default('-'),
                        TextEntry::make('kecamatan')
                            ->label('Kecamatan')
                            ->default('-'),
                        TextEntry::make('kabupaten')
                            ->label('Kabupaten/Kota')
                            ->default('-'),
                        TextEntry::make('provinsi')
                            ->label('Provinsi')
                            ->default('-'),
                        TextEntry::make('kode_pos')
                            ->label('Kode Pos')
                            ->default('-'),
                    ])
                    ->columns(3),

                Section::make('Kontak')
                    ->schema([
                        TextEntry::make('telepon')
                            ->label('Telepon')
                            ->default('-'),
                        TextEntry::make('fax')
                            ->label('Fax')
                            ->default('-'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->default('-'),
                        TextEntry::make('website')
                            ->label('Website')
                            ->url(fn ($state) => $state)
                            ->default('-'),
                    ])
                    ->columns(2),

                Section::make('Kepala Sekolah')
                    ->schema([
                        TextEntry::make('kepala_sekolah')
                            ->label('Nama Kepala Sekolah')
                            ->default('-'),
                        TextEntry::make('nip_kepala_sekolah')
                            ->label('NIP Kepala Sekolah')
                            ->default('-'),
                    ])
                    ->columns(2),

                Section::make('Akreditasi & SK')
                    ->schema([
                        TextEntry::make('akreditasi')
                            ->label('Akreditasi')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'A' => 'success',
                                'B' => 'info',
                                'C' => 'warning',
                                default => 'gray',
                            })
                            ->default('-'),
                        TextEntry::make('tanggal_akreditasi')
                            ->label('Tanggal Akreditasi')
                            ->date('d F Y')
                            ->default('-'),
                        TextEntry::make('no_sk_operasional')
                            ->label('No. SK Operasional')
                            ->default('-'),
                        TextEntry::make('tanggal_sk_operasional')
                            ->label('Tanggal SK Operasional')
                            ->date('d F Y')
                            ->default('-'),
                    ])
                    ->columns(2),

                Section::make('Visi & Misi')
                    ->schema([
                        TextEntry::make('visi')
                            ->label('Visi')
                            ->markdown()
                            ->columnSpanFull()
                            ->default('-'),
                        TextEntry::make('misi')
                            ->label('Misi')
                            ->markdown()
                            ->columnSpanFull()
                            ->default('-'),
                    ]),

                Section::make('Logo & Status')
                    ->schema([
                        ImageEntry::make('logo')
                            ->label('Logo Sekolah')
                            ->circular()
                            ->size(100)
                            ->defaultImageUrl(fn () => asset('images/default-logo.png')),
                        IconEntry::make('is_active')
                            ->label('Status Aktif')
                            ->boolean(),
                    ])
                    ->columns(2),
            ]);
    }
}
