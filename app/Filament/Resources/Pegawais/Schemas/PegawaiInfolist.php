<?php

namespace App\Filament\Resources\Pegawais\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PegawaiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    Section::make('Foto')
                        ->columnSpan(1)
                        ->schema([
                            ImageEntry::make('foto')
                                ->label('')
                                ->circular()
                                ->size(180)
                                ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->nama).'&size=180&background=random'),
                            TextEntry::make('is_active')
                                ->label('Status')
                                ->badge()
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Non-Aktif')
                                ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                            TextEntry::make('tanggal_keluar')
                                ->label('Tanggal Keluar')
                                ->date('d F Y')
                                ->placeholder('-'),
                        ]),

                    Section::make('Data Pegawai')
                        ->columnSpan(2)
                        ->schema([
                            Grid::make(3)->schema([
                                TextEntry::make('nip')
                                    ->label('NIP')
                                    ->placeholder('-')
                                    ->copyable(),
                                TextEntry::make('nuptk')
                                    ->label('NUPTK')
                                    ->placeholder('-')
                                    ->copyable(),
                                TextEntry::make('jabatan.nama')
                                    ->label('Jabatan')
                                    ->placeholder('-')
                                    ->badge()
                                    ->color('primary'),
                            ]),
                            Grid::make(3)->schema([
                                TextEntry::make('status_kepegawaian')
                                    ->label('Status Kepegawaian')
                                    ->badge(),
                                TextEntry::make('tanggal_masuk')
                                    ->label('Tanggal Masuk')
                                    ->date('d F Y')
                                    ->placeholder('-'),
                                TextEntry::make('masa_kerja')
                                    ->label('Masa Kerja')
                                    ->placeholder('-'),
                            ]),
                            Grid::make(3)->schema([
                                TextEntry::make('nama')
                                    ->label('Nama Lengkap')
                                    ->weight('bold'),
                                TextEntry::make('jenis_kelamin')
                                    ->label('Jenis Kelamin')
                                    ->formatStateUsing(fn (string $state): string => $state === 'L' ? 'Laki-laki' : 'Perempuan')
                                    ->badge(),
                                TextEntry::make('umur')
                                    ->label('Umur')
                                    ->suffix(' tahun')
                                    ->placeholder('-'),
                            ]),
                            Grid::make(3)->schema([
                                TextEntry::make('tempat_lahir')
                                    ->label('Tempat Lahir')
                                    ->placeholder('-'),
                                TextEntry::make('tanggal_lahir')
                                    ->label('Tanggal Lahir')
                                    ->date('d F Y')
                                    ->placeholder('-'),
                                TextEntry::make('agama')
                                    ->label('Agama')
                                    ->placeholder('-'),
                            ]),
                            Grid::make(3)->schema([
                                TextEntry::make('status_pernikahan')
                                    ->label('Status Pernikahan')
                                    ->placeholder('-'),
                                TextEntry::make('jumlah_tanggungan')
                                    ->label('Tanggungan')
                                    ->placeholder('0')
                                    ->suffix(' orang'),
                                TextEntry::make('email')
                                    ->label('Email')
                                    ->placeholder('-')
                                    ->copyable(),
                            ]),
                            Grid::make(2)->schema([
                                TextEntry::make('telepon')
                                    ->label('Telepon')
                                    ->placeholder('-')
                                    ->copyable(),
                                TextEntry::make('alamat')
                                    ->label('Alamat')
                                    ->placeholder('-'),
                            ]),
                            Grid::make(4)->schema([
                                TextEntry::make('pendidikan_terakhir')
                                    ->label('Pendidikan')
                                    ->placeholder('-'),
                                TextEntry::make('jurusan')
                                    ->label('Jurusan')
                                    ->placeholder('-'),
                                TextEntry::make('universitas')
                                    ->label('Institusi')
                                    ->placeholder('-'),
                                TextEntry::make('tahun_lulus')
                                    ->label('Tahun Lulus')
                                    ->placeholder('-'),
                            ]),
                            Grid::make(3)->schema([
                                TextEntry::make('nama_bank')
                                    ->label('Bank')
                                    ->placeholder('-'),
                                TextEntry::make('no_rekening')
                                    ->label('No. Rekening')
                                    ->placeholder('-')
                                    ->copyable(),
                                TextEntry::make('npwp')
                                    ->label('NPWP')
                                    ->placeholder('-')
                                    ->copyable(),
                            ]),
                            Grid::make(2)->schema([
                                TextEntry::make('no_bpjs_kesehatan')
                                    ->label('BPJS Kesehatan')
                                    ->placeholder('-')
                                    ->copyable(),
                                TextEntry::make('no_bpjs_ketenagakerjaan')
                                    ->label('BPJS Ketenagakerjaan')
                                    ->placeholder('-')
                                    ->copyable(),
                            ]),
                            Grid::make(2)->schema([
                                TextEntry::make('user.name')
                                    ->label('User')
                                    ->placeholder('-'),
                                TextEntry::make('user.email')
                                    ->label('Email User')
                                    ->placeholder('-')
                                    ->copyable(),
                            ]),
                        ]),
                ]),
            ]);
    }
}
