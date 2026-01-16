<?php

namespace App\Filament\Resources\Pegawais\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class PegawaiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        // Main Content - 2 columns
                        Group::make()
                            ->columnSpan(2)
                            ->schema([
                                Section::make('Data Kepegawaian')
                                    ->icon('heroicon-o-identification')
                                    ->collapsible()
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
                                            TextEntry::make('nik')
                                                ->label('NIK')
                                                ->copyable(),
                                        ]),
                                        Grid::make(3)->schema([
                                            TextEntry::make('jabatan.nama')
                                                ->label('Jabatan')
                                                ->badge()
                                                ->color('primary'),
                                            TextEntry::make('status_kepegawaian')
                                                ->label('Status Kepegawaian')
                                                ->badge()
                                                ->color(fn (string $state): string => match ($state) {
                                                    'PNS' => 'success',
                                                    'PPPK' => 'info',
                                                    'Honorer' => 'warning',
                                                    'Kontrak' => 'gray',
                                                    default => 'gray',
                                                }),
                                            TextEntry::make('tanggal_masuk')
                                                ->label('Tanggal Masuk')
                                                ->date('d F Y'),
                                        ]),
                                        Grid::make(3)->schema([
                                            TextEntry::make('golongan')
                                                ->label('Golongan')
                                                ->placeholder('-'),
                                            TextEntry::make('tmt_golongan')
                                                ->label('TMT Golongan')
                                                ->placeholder('-'),
                                            TextEntry::make('masa_kerja')
                                                ->label('Masa Kerja')
                                                ->suffix(' tahun')
                                                ->badge()
                                                ->color('success'),
                                        ]),
                                    ]),

                                Section::make('Data Pribadi')
                                    ->icon('heroicon-o-user')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextEntry::make('nama_lengkap')
                                                ->label('Nama Lengkap')
                                                ->weight(FontWeight::Bold),
                                            TextEntry::make('jenis_kelamin')
                                                ->label('Jenis Kelamin')
                                                ->badge()
                                                ->color(fn (string $state): string => match ($state) {
                                                    'Laki-laki' => 'info',
                                                    'Perempuan' => 'pink',
                                                    default => 'gray',
                                                }),
                                            TextEntry::make('umur')
                                                ->label('Umur')
                                                ->suffix(' tahun'),
                                        ]),
                                        Grid::make(3)->schema([
                                            TextEntry::make('tempat_tanggal_lahir')
                                                ->label('Tempat, Tanggal Lahir')
                                                ->state(fn ($record): string => $record->tempat_lahir.', '.$record->tanggal_lahir?->format('d F Y')),
                                            TextEntry::make('agama')
                                                ->label('Agama'),
                                            TextEntry::make('status_perkawinan')
                                                ->label('Status Perkawinan')
                                                ->placeholder('-'),
                                        ]),
                                        TextEntry::make('jumlah_tanggungan')
                                            ->label('Jumlah Tanggungan')
                                            ->suffix(' orang')
                                            ->placeholder('0'),
                                    ]),

                                Section::make('Alamat & Kontak')
                                    ->icon('heroicon-o-map-pin')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        TextEntry::make('alamat_lengkap')
                                            ->label('Alamat')
                                            ->state(function ($record): string {
                                                $parts = array_filter([
                                                    $record->alamat,
                                                    $record->rt ? 'RT '.$record->rt : null,
                                                    $record->rw ? 'RW '.$record->rw : null,
                                                    $record->kelurahan,
                                                    $record->kecamatan,
                                                    $record->kota,
                                                    $record->provinsi,
                                                    $record->kode_pos,
                                                ]);

                                                return implode(', ', $parts) ?: '-';
                                            })
                                            ->columnSpanFull(),
                                        Grid::make(2)->schema([
                                            TextEntry::make('telepon')
                                                ->label('Telepon')
                                                ->placeholder('-')
                                                ->copyable()
                                                ->icon('heroicon-o-phone'),
                                            TextEntry::make('email')
                                                ->label('Email')
                                                ->placeholder('-')
                                                ->copyable()
                                                ->icon('heroicon-o-envelope'),
                                        ]),
                                    ]),

                                Section::make('Pendidikan')
                                    ->icon('heroicon-o-academic-cap')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextEntry::make('pendidikan_terakhir')
                                                ->label('Pendidikan Terakhir')
                                                ->badge()
                                                ->color('info')
                                                ->placeholder('-'),
                                            TextEntry::make('jurusan')
                                                ->label('Jurusan')
                                                ->placeholder('-'),
                                            TextEntry::make('tahun_lulus')
                                                ->label('Tahun Lulus')
                                                ->placeholder('-'),
                                        ]),
                                        TextEntry::make('nama_perguruan_tinggi')
                                            ->label('Nama Perguruan Tinggi')
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Data Bank & BPJS')
                                    ->icon('heroicon-o-credit-card')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextEntry::make('nama_bank')
                                                ->label('Bank')
                                                ->placeholder('-'),
                                            TextEntry::make('nomor_rekening')
                                                ->label('No. Rekening')
                                                ->placeholder('-')
                                                ->copyable(),
                                            TextEntry::make('nama_pemilik_rekening')
                                                ->label('Atas Nama')
                                                ->placeholder('-'),
                                        ]),
                                        Grid::make(3)->schema([
                                            TextEntry::make('no_bpjs_kesehatan')
                                                ->label('BPJS Kesehatan')
                                                ->placeholder('-')
                                                ->copyable(),
                                            TextEntry::make('no_bpjs_ketenagakerjaan')
                                                ->label('BPJS Ketenagakerjaan')
                                                ->placeholder('-')
                                                ->copyable(),
                                            TextEntry::make('npwp')
                                                ->label('NPWP')
                                                ->placeholder('-')
                                                ->copyable(),
                                        ]),
                                    ]),
                            ]),

                        // Sidebar - 1 column
                        Group::make()
                            ->columnSpan(1)
                            ->schema([
                                Section::make('Foto')
                                    ->schema([
                                        ImageEntry::make('foto')
                                            ->label('')
                                            ->circular()
                                            ->size(200)
                                            ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name='.urlencode($record->nama).'&size=200&background=random'),
                                    ]),

                                Section::make('Status')
                                    ->schema([
                                        TextEntry::make('is_active')
                                            ->label('Status')
                                            ->badge()
                                            ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Non-Aktif')
                                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                                        TextEntry::make('tanggal_keluar')
                                            ->label('Tanggal Keluar')
                                            ->date('d F Y')
                                            ->placeholder('-')
                                            ->visible(fn ($record): bool => $record->tanggal_keluar !== null),
                                    ]),

                                Section::make('User Account')
                                    ->schema([
                                        TextEntry::make('user.name')
                                            ->label('Nama User')
                                            ->placeholder('Belum terhubung'),
                                        TextEntry::make('user.email')
                                            ->label('Email User')
                                            ->placeholder('-')
                                            ->copyable(),
                                    ]),

                                Section::make('Catatan')
                                    ->schema([
                                        TextEntry::make('catatan')
                                            ->label('')
                                            ->placeholder('Tidak ada catatan')
                                            ->markdown(),
                                    ]),

                                Section::make('Informasi Sistem')
                                    ->collapsed()
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label('Dibuat')
                                            ->dateTime('d M Y H:i'),
                                        TextEntry::make('updated_at')
                                            ->label('Diperbarui')
                                            ->dateTime('d M Y H:i'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
