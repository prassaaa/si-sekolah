<?php

namespace App\Filament\Resources\Pegawais\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PegawaiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Kepegawaian')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('nip')
                                ->label('NIP')
                                ->maxLength(30)
                                ->unique(ignoreRecord: true)
                                ->helperText('Opsional'),
                            TextInput::make('nuptk')
                                ->label('NUPTK')
                                ->maxLength(30)
                                ->unique(ignoreRecord: true)
                                ->helperText('Opsional'),
                            Select::make('jabatan_id')
                                ->label('Jabatan')
                                ->relationship('jabatan', 'nama')
                                ->searchable()
                                ->preload(),
                        ]),
                        Grid::make(3)->schema([
                            Select::make('status_kepegawaian')
                                ->label('Status Kepegawaian')
                                ->options([
                                    'PNS' => 'PNS',
                                    'PPPK' => 'PPPK',
                                    'GTY' => 'GTY',
                                    'GTT' => 'GTT',
                                    'PTY' => 'PTY',
                                    'PTT' => 'PTT',
                                ])
                                ->required()
                                ->default('GTT')
                                ->native(false),
                            DatePicker::make('tanggal_masuk')
                                ->label('Tanggal Masuk')
                                ->native(false),
                            Select::make('user_id')
                                ->label('User Account')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->preload(),
                        ]),
                    ]),

                Section::make('Data Pribadi')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('nama')
                                ->label('Nama Lengkap')
                                ->required()
                                ->maxLength(255),
                            Select::make('jenis_kelamin')
                                ->label('Jenis Kelamin')
                                ->options([
                                    'L' => 'Laki-laki',
                                    'P' => 'Perempuan',
                                ])
                                ->required()
                                ->native(false),
                            Select::make('agama')
                                ->label('Agama')
                                ->options([
                                    'Islam' => 'Islam',
                                    'Kristen' => 'Kristen',
                                    'Katolik' => 'Katolik',
                                    'Hindu' => 'Hindu',
                                    'Buddha' => 'Buddha',
                                    'Konghucu' => 'Konghucu',
                                ])
                                ->default('Islam')
                                ->required()
                                ->native(false),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('tempat_lahir')
                                ->label('Tempat Lahir')
                                ->maxLength(255),
                            DatePicker::make('tanggal_lahir')
                                ->label('Tanggal Lahir')
                                ->native(false),
                            Select::make('status_pernikahan')
                                ->label('Status Pernikahan')
                                ->options([
                                    'Belum Menikah' => 'Belum Menikah',
                                    'Menikah' => 'Menikah',
                                    'Cerai' => 'Cerai',
                                ])
                                ->default('Belum Menikah')
                                ->native(false),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('jumlah_tanggungan')
                                ->label('Jumlah Tanggungan')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('telepon')
                                ->label('Telepon')
                                ->tel()
                                ->maxLength(20),
                            DatePicker::make('tanggal_keluar')
                                ->label('Tanggal Keluar')
                                ->native(false),
                        ]),
                        Textarea::make('alamat')
                            ->label('Alamat')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Pendidikan')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('pendidikan_terakhir')
                                ->label('Pendidikan Terakhir')
                                ->options([
                                    'SD' => 'SD',
                                    'SMP' => 'SMP',
                                    'SMA' => 'SMA',
                                    'D1' => 'D1',
                                    'D2' => 'D2',
                                    'D3' => 'D3',
                                    'D4' => 'D4',
                                    'S1' => 'S1',
                                    'S2' => 'S2',
                                    'S3' => 'S3',
                                ])
                                ->native(false),
                            TextInput::make('jurusan')
                                ->label('Jurusan')
                                ->maxLength(255),
                            TextInput::make('universitas')
                                ->label('Institusi')
                                ->maxLength(255),
                        ]),
                        TextInput::make('tahun_lulus')
                            ->label('Tahun Lulus')
                            ->numeric()
                            ->minValue(1970)
                            ->maxValue((int) date('Y')),
                    ]),

                Section::make('Data Bank & BPJS')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('nama_bank')
                                ->label('Nama Bank')
                                ->maxLength(50),
                            TextInput::make('no_rekening')
                                ->label('No. Rekening')
                                ->maxLength(30),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('npwp')
                                ->label('NPWP')
                                ->maxLength(30),
                            TextInput::make('no_bpjs_kesehatan')
                                ->label('BPJS Kesehatan')
                                ->maxLength(30),
                            TextInput::make('no_bpjs_ketenagakerjaan')
                                ->label('BPJS Ketenagakerjaan')
                                ->maxLength(30),
                        ]),
                    ]),

                Section::make('Foto & Status')
                    ->schema([
                        FileUpload::make('foto')
                            ->label('Foto')
                            ->image()
                            ->imageEditor()
                            ->directory('pegawai-photos')
                            ->maxSize(2048),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
