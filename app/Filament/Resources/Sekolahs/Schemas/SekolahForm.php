<?php

namespace App\Filament\Resources\Sekolahs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SekolahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Sekolah')
                    ->schema([
                        TextInput::make('npsn')
                            ->label('NPSN')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),
                        TextInput::make('nama')
                            ->label('Nama Sekolah')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nama_yayasan')
                            ->label('Nama Yayasan')
                            ->maxLength(255),
                        Select::make('jenjang')
                            ->label('Jenjang')
                            ->options([
                                'TK' => 'TK',
                                'RA' => 'RA',
                                'SD' => 'SD',
                                'MI' => 'MI',
                                'SMP' => 'SMP',
                                'MTs' => 'MTs',
                                'SMA' => 'SMA',
                                'MA' => 'MA',
                                'SMK' => 'SMK',
                            ])
                            ->required()
                            ->searchable(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'Negeri' => 'Negeri',
                                'Swasta' => 'Swasta',
                            ])
                            ->default('Swasta')
                            ->required(),
                        TextInput::make('tahun_berdiri')
                            ->label('Tahun Berdiri')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(date('Y')),
                    ])
                    ->columns(2),

                Section::make('Alamat')
                    ->schema([
                        Textarea::make('alamat')
                            ->label('Alamat Lengkap')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('kelurahan')
                            ->label('Kelurahan/Desa')
                            ->maxLength(255),
                        TextInput::make('kecamatan')
                            ->label('Kecamatan')
                            ->maxLength(255),
                        TextInput::make('kabupaten')
                            ->label('Kabupaten/Kota')
                            ->maxLength(255),
                        TextInput::make('provinsi')
                            ->label('Provinsi')
                            ->maxLength(255),
                        TextInput::make('kode_pos')
                            ->label('Kode Pos')
                            ->maxLength(10),
                    ])
                    ->columns(2),

                Section::make('Kontak')
                    ->schema([
                        TextInput::make('telepon')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('fax')
                            ->label('Fax')
                            ->maxLength(20),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('website')
                            ->label('Website')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Kepala Sekolah')
                    ->schema([
                        TextInput::make('kepala_sekolah')
                            ->label('Nama Kepala Sekolah')
                            ->maxLength(255),
                        TextInput::make('nip_kepala_sekolah')
                            ->label('NIP Kepala Sekolah')
                            ->maxLength(30),
                    ])
                    ->columns(2),

                Section::make('Akreditasi & SK')
                    ->schema([
                        Select::make('akreditasi')
                            ->label('Akreditasi')
                            ->options([
                                'A' => 'A (Unggul)',
                                'B' => 'B (Baik)',
                                'C' => 'C (Cukup)',
                                'TT' => 'Tidak Terakreditasi',
                            ]),
                        DatePicker::make('tanggal_akreditasi')
                            ->label('Tanggal Akreditasi'),
                        TextInput::make('no_sk_operasional')
                            ->label('No. SK Operasional')
                            ->maxLength(255),
                        DatePicker::make('tanggal_sk_operasional')
                            ->label('Tanggal SK Operasional'),
                    ])
                    ->columns(2),

                Section::make('Visi & Misi')
                    ->schema([
                        Textarea::make('visi')
                            ->label('Visi')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('misi')
                            ->label('Misi')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Section::make('Logo & Status')
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Logo Sekolah')
                            ->image()
                            ->directory('sekolah/logo')
                            ->maxSize(2048),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
