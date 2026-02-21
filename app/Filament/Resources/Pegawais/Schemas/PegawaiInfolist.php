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
        return $schema->components([
            Grid::make([
                'default' => 1,
                'xl' => 3,
            ])->schema([
                Section::make('Profil')
                    ->inlineLabel(false)
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 1,
                    ])
                    ->schema([
                        ImageEntry::make('foto')
                            ->hiddenLabel()
                            ->alignCenter()
                            ->circular()
                            ->imageSize(112)
                            ->extraImgAttributes([
                                'class' => 'mx-auto ring-2 ring-gray-200 dark:ring-gray-700',
                            ])
                            ->defaultImageUrl(fn ($record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->nama) . '&size=112&background=e2e8f0&color=111827'),
                        TextEntry::make('nama')
                            ->hiddenLabel()
                            ->alignCenter()
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('jabatan.nama')
                            ->hiddenLabel()
                            ->alignCenter()
                            ->placeholder('-')
                            ->badge()
                            ->color('primary'),
                        Grid::make([
                            'default' => 1,
                        ])->schema([
                            TextEntry::make('status_kepegawaian')
                                ->label('Status Kepegawaian')
                                ->alignCenter()
                                ->badge(),
                            TextEntry::make('is_active')
                                ->label('Status')
                                ->alignCenter()
                                ->badge()
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Non-Aktif')
                                ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                        ]),
                    ]),
                Section::make('Data Kepegawaian')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 2,
                    ])
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 3,
                        ])->schema([
                            TextEntry::make('nip')
                                ->label('NIP')
                                ->placeholder('-')
                                ->copyable(),
                            TextEntry::make('nuptk')
                                ->label('NUPTK')
                                ->placeholder('-')
                                ->copyable(),
                            TextEntry::make('masa_kerja')
                                ->label('Masa Kerja')
                                ->placeholder('-'),
                        ]),
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            TextEntry::make('tanggal_masuk')
                                ->label('Tanggal Masuk')
                                ->date('d F Y')
                                ->placeholder('-'),
                            TextEntry::make('tanggal_keluar')
                                ->label('Tanggal Keluar')
                                ->date('d F Y')
                                ->placeholder('-'),
                        ]),
                    ]),
            ]),

            Section::make('Data Pribadi')
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])->schema([
                        TextEntry::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->formatStateUsing(fn (string $state): string => $state === 'L' ? 'Laki-laki' : 'Perempuan')
                            ->badge(),
                        TextEntry::make('agama')
                            ->label('Agama')
                            ->placeholder('-'),
                        TextEntry::make('status_pernikahan')
                            ->label('Status Pernikahan')
                            ->placeholder('-'),
                    ]),
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])->schema([
                        TextEntry::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->placeholder('-'),
                        TextEntry::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->date('d F Y')
                            ->placeholder('-'),
                        TextEntry::make('umur')
                            ->label('Umur')
                            ->suffix(' tahun')
                            ->placeholder('-'),
                    ]),
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        TextEntry::make('telepon')
                            ->label('Telepon')
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('email')
                            ->label('Email')
                            ->placeholder('-')
                            ->copyable(),
                    ]),
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        TextEntry::make('jumlah_tanggungan')
                            ->label('Tanggungan')
                            ->placeholder('0')
                            ->suffix(' orang'),
                        TextEntry::make('alamat')
                            ->label('Alamat')
                            ->placeholder('-'),
                    ]),
                ]),

            Section::make('Pendidikan')
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])->schema([
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
                ]),

            Section::make('Bank & BPJS')
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])->schema([
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
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        TextEntry::make('no_bpjs_kesehatan')
                            ->label('BPJS Kesehatan')
                            ->placeholder('-')
                            ->copyable(),
                        TextEntry::make('no_bpjs_ketenagakerjaan')
                            ->label('BPJS Ketenagakerjaan')
                            ->placeholder('-')
                            ->copyable(),
                    ]),
                ]),

            Section::make('Akun Sistem')
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'md' => 2,
                    ])->schema([
                        TextEntry::make('user.name')
                            ->label('User')
                            ->placeholder('-'),
                        TextEntry::make('user.email')
                            ->label('Email User')
                            ->placeholder('-')
                            ->copyable(),
                    ]),
                ]),
        ]);
    }
}
