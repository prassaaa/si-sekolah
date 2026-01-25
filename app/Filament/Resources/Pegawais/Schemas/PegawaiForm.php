<?php

namespace App\Filament\Resources\Pegawais\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PegawaiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns(3)
                    ->schema([
                        // Main Content - 2 columns
                        Grid::make()
                            ->columnSpan(2)
                            ->schema([
                                Section::make('Data Kepegawaian')
                                    ->icon('heroicon-o-identification')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('nip')
                                                ->label('NIP')
                                                ->maxLength(18)
                                                ->unique(ignoreRecord: true)
                                                ->helperText('18 digit untuk PNS'),
                                            TextInput::make('nuptk')
                                                ->label('NUPTK')
                                                ->maxLength(16)
                                                ->unique(ignoreRecord: true)
                                                ->helperText('16 digit untuk guru'),
                                            TextInput::make('nik')
                                                ->label('NIK')
                                                ->required()
                                                ->maxLength(16)
                                                ->unique(ignoreRecord: true),
                                        ]),
                                        Grid::make(3)->schema([
                                            Select::make('jabatan_id')
                                                ->label('Jabatan')
                                                ->relationship('jabatan', 'nama')
                                                ->searchable()
                                                ->preload()
                                                ->required(),
                                            Select::make('status_kepegawaian')
                                                ->label('Status Kepegawaian')
                                                ->options([
                                                    'PNS' => 'PNS',
                                                    'PPPK' => 'PPPK',
                                                    'Honorer' => 'Honorer',
                                                    'Kontrak' => 'Kontrak',
                                                ])
                                                ->required()
                                                ->native(false),
                                            DatePicker::make('tanggal_masuk')
                                                ->label('Tanggal Masuk')
                                                ->required()
                                                ->native(false)
                                                ->displayFormat('d/m/Y'),
                                        ]),
                                        Grid::make(3)->schema([
                                            TextInput::make('golongan')
                                                ->label('Golongan')
                                                ->maxLength(10)
                                                ->helperText('Contoh: III/a'),
                                            TextInput::make('tmt_golongan')
                                                ->label('TMT Golongan')
                                                ->maxLength(20),
                                            Select::make('user_id')
                                                ->label('User Account')
                                                ->relationship('user', 'name')
                                                ->searchable()
                                                ->preload()
                                                ->helperText('Link ke akun user untuk login'),
                                        ]),
                                    ]),

                                Section::make('Data Pribadi')
                                    ->icon('heroicon-o-user')
                                    ->collapsible()
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('nama')
                                                ->label('Nama Lengkap')
                                                ->required()
                                                ->maxLength(100),
                                            TextInput::make('gelar_depan')
                                                ->label('Gelar Depan')
                                                ->maxLength(20),
                                            TextInput::make('gelar_belakang')
                                                ->label('Gelar Belakang')
                                                ->maxLength(20),
                                        ]),
                                        Grid::make(3)->schema([
                                            Select::make('jenis_kelamin')
                                                ->label('Jenis Kelamin')
                                                ->options([
                                                    'Laki-laki' => 'Laki-laki',
                                                    'Perempuan' => 'Perempuan',
                                                ])
                                                ->required()
                                                ->native(false),
                                            TextInput::make('tempat_lahir')
                                                ->label('Tempat Lahir')
                                                ->required()
                                                ->maxLength(50),
                                            DatePicker::make('tanggal_lahir')
                                                ->label('Tanggal Lahir')
                                                ->required()
                                                ->native(false)
                                                ->displayFormat('d/m/Y'),
                                        ]),
                                        Grid::make(3)->schema([
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
                                                ->required()
                                                ->native(false),
                                            Select::make('status_perkawinan')
                                                ->label('Status Perkawinan')
                                                ->options([
                                                    'Belum Kawin' => 'Belum Kawin',
                                                    'Kawin' => 'Kawin',
                                                    'Cerai Hidup' => 'Cerai Hidup',
                                                    'Cerai Mati' => 'Cerai Mati',
                                                ])
                                                ->native(false),
                                            TextInput::make('jumlah_tanggungan')
                                                ->label('Jumlah Tanggungan')
                                                ->numeric()
                                                ->minValue(0)
                                                ->default(0),
                                        ]),
                                    ]),

                                Section::make('Alamat & Kontak')
                                    ->icon('heroicon-o-map-pin')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Textarea::make('alamat')
                                            ->label('Alamat Lengkap')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                        Grid::make(4)->schema([
                                            TextInput::make('rt')
                                                ->label('RT')
                                                ->maxLength(3),
                                            TextInput::make('rw')
                                                ->label('RW')
                                                ->maxLength(3),
                                            TextInput::make('kelurahan')
                                                ->label('Kelurahan/Desa')
                                                ->maxLength(50),
                                            TextInput::make('kecamatan')
                                                ->label('Kecamatan')
                                                ->maxLength(50),
                                        ]),
                                        Grid::make(3)->schema([
                                            TextInput::make('kota')
                                                ->label('Kota/Kabupaten')
                                                ->maxLength(50),
                                            TextInput::make('provinsi')
                                                ->label('Provinsi')
                                                ->maxLength(50),
                                            TextInput::make('kode_pos')
                                                ->label('Kode Pos')
                                                ->maxLength(5),
                                        ]),
                                        Grid::make(2)->schema([
                                            TextInput::make('telepon')
                                                ->label('Telepon')
                                                ->tel()
                                                ->maxLength(15),
                                            TextInput::make('email')
                                                ->label('Email')
                                                ->email()
                                                ->maxLength(100)
                                                ->unique(ignoreRecord: true),
                                        ]),
                                    ]),

                                Section::make('Pendidikan')
                                    ->icon('heroicon-o-academic-cap')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Select::make('pendidikan_terakhir')
                                                ->label('Pendidikan Terakhir')
                                                ->options([
                                                    'SD' => 'SD',
                                                    'SMP' => 'SMP',
                                                    'SMA/SMK' => 'SMA/SMK',
                                                    'D1' => 'D1',
                                                    'D2' => 'D2',
                                                    'D3' => 'D3',
                                                    'D4/S1' => 'D4/S1',
                                                    'S2' => 'S2',
                                                    'S3' => 'S3',
                                                ])
                                                ->native(false),
                                            TextInput::make('jurusan')
                                                ->label('Jurusan/Program Studi')
                                                ->maxLength(100),
                                            TextInput::make('tahun_lulus')
                                                ->label('Tahun Lulus')
                                                ->numeric()
                                                ->minValue(1970)
                                                ->maxValue(date('Y')),
                                        ]),
                                        TextInput::make('nama_perguruan_tinggi')
                                            ->label('Nama Perguruan Tinggi/Sekolah')
                                            ->maxLength(100)
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Data Bank & BPJS')
                                    ->icon('heroicon-o-credit-card')
                                    ->collapsible()
                                    ->collapsed()
                                    ->schema([
                                        Fieldset::make('Rekening Bank')
                                            ->schema([
                                                Grid::make(3)->schema([
                                                    TextInput::make('nama_bank')
                                                        ->label('Nama Bank')
                                                        ->maxLength(50),
                                                    TextInput::make('nomor_rekening')
                                                        ->label('Nomor Rekening')
                                                        ->maxLength(30),
                                                    TextInput::make('nama_pemilik_rekening')
                                                        ->label('Nama Pemilik Rekening')
                                                        ->maxLength(100),
                                                ]),
                                            ]),
                                        Fieldset::make('BPJS')
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    TextInput::make('no_bpjs_kesehatan')
                                                        ->label('No. BPJS Kesehatan')
                                                        ->maxLength(20),
                                                    TextInput::make('no_bpjs_ketenagakerjaan')
                                                        ->label('No. BPJS Ketenagakerjaan')
                                                        ->maxLength(20),
                                                ]),
                                            ]),
                                        TextInput::make('npwp')
                                            ->label('NPWP')
                                            ->maxLength(20),
                                    ]),
                            ]),

                        // Sidebar - 1 column
                        Grid::make()
                            ->columnSpan(1)
                            ->schema([
                                Section::make('Foto')
                                    ->schema([
                                        FileUpload::make('foto')
                                            ->label('')
                                            ->image()
                                            ->imageEditor()
                                            ->circleCropper()
                                            ->directory('pegawai-photos')
                                            ->maxSize(2048)
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                                    ]),

                                Section::make('Status')
                                    ->schema([
                                        Toggle::make('is_active')
                                            ->label('Aktif')
                                            ->default(true)
                                            ->helperText('Pegawai masih bekerja di sekolah'),
                                        DatePicker::make('tanggal_keluar')
                                            ->label('Tanggal Keluar')
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->helperText('Diisi jika sudah keluar'),
                                    ]),

                                Section::make('Catatan')
                                    ->schema([
                                        Textarea::make('catatan')
                                            ->label('')
                                            ->rows(4)
                                            ->placeholder('Catatan tambahan...'),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
