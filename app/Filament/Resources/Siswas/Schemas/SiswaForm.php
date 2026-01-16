<?php

namespace App\Filament\Resources\Siswas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Data Siswa')
                    ->tabs([
                        Tab::make('Identitas')
                            ->icon('heroicon-o-user')
                            ->schema(self::identitasSchema()),

                        Tab::make('Alamat & Kontak')
                            ->icon('heroicon-o-map-pin')
                            ->schema(self::alamatKontakSchema()),

                        Tab::make('Akademik')
                            ->icon('heroicon-o-academic-cap')
                            ->schema(self::akademikSchema()),

                        Tab::make('Kesehatan')
                            ->icon('heroicon-o-heart')
                            ->schema(self::kesehatanSchema()),

                        Tab::make('Data Ayah')
                            ->icon('heroicon-o-user')
                            ->schema(self::dataAyahSchema()),

                        Tab::make('Data Ibu')
                            ->icon('heroicon-o-user')
                            ->schema(self::dataIbuSchema()),

                        Tab::make('Data Wali')
                            ->icon('heroicon-o-users')
                            ->schema(self::dataWaliSchema()),

                        Tab::make('Dokumen')
                            ->icon('heroicon-o-document')
                            ->schema(self::dokumenSchema()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function identitasSchema(): array
    {
        return [
            Section::make('Nomor Induk')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('nis')
                            ->label('NIS')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('Nomor Induk Siswa'),
                        TextInput::make('nisn')
                            ->label('NISN')
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('Nomor Induk Siswa Nasional'),
                    ]),
                ]),

            Section::make('Data Pribadi')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('nama_panggilan')
                            ->label('Nama Panggilan')
                            ->maxLength(50),
                    ]),
                    Grid::make(3)->schema([
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->required()
                            ->native(false)
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ]),
                        TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->maxLength(50),
                        DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ]),
                    Grid::make(3)->schema([
                        TextInput::make('nik')
                            ->label('NIK')
                            ->maxLength(20)
                            ->placeholder('Nomor KTP'),
                        TextInput::make('no_kk')
                            ->label('No. KK')
                            ->maxLength(20)
                            ->placeholder('Nomor Kartu Keluarga'),
                        TextInput::make('no_akta')
                            ->label('No. Akta')
                            ->maxLength(30)
                            ->placeholder('Nomor Akta Kelahiran'),
                    ]),
                    Grid::make(4)->schema([
                        Select::make('agama')
                            ->label('Agama')
                            ->native(false)
                            ->options([
                                'Islam' => 'Islam',
                                'Kristen' => 'Kristen',
                                'Katolik' => 'Katolik',
                                'Hindu' => 'Hindu',
                                'Buddha' => 'Buddha',
                                'Konghucu' => 'Konghucu',
                            ]),
                        TextInput::make('kewarganegaraan')
                            ->label('Kewarganegaraan')
                            ->default('Indonesia')
                            ->maxLength(50),
                        TextInput::make('anak_ke')
                            ->label('Anak Ke')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('jumlah_saudara')
                            ->label('Jumlah Saudara')
                            ->numeric()
                            ->minValue(0),
                    ]),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function alamatKontakSchema(): array
    {
        return [
            Section::make('Alamat')
                ->schema([
                    Textarea::make('alamat')
                        ->label('Alamat')
                        ->rows(2),
                    Grid::make(4)->schema([
                        TextInput::make('rt')
                            ->label('RT')
                            ->maxLength(5),
                        TextInput::make('rw')
                            ->label('RW')
                            ->maxLength(5),
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
                            ->maxLength(10),
                    ]),
                ]),

            Section::make('Kontak')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('telepon')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('hp')
                            ->label('No. HP')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(100),
                    ]),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function akademikSchema(): array
    {
        return [
            Section::make('Data Akademik')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->relationship('kelas', 'nama')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih kelas'),
                        DatePicker::make('tanggal_masuk')
                            ->label('Tanggal Masuk')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('asal_sekolah')
                            ->label('Asal Sekolah')
                            ->maxLength(100),
                        TextInput::make('tahun_masuk')
                            ->label('Tahun Masuk')
                            ->numeric()
                            ->minValue(2000)
                            ->maxValue(date('Y') + 1),
                    ]),
                    Grid::make(2)->schema([
                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->native(false)
                            ->default('aktif')
                            ->options([
                                'aktif' => 'Aktif',
                                'lulus' => 'Lulus',
                                'pindah' => 'Pindah',
                                'dikeluarkan' => 'Dikeluarkan',
                                'dropout' => 'Dropout',
                                'tidak_aktif' => 'Tidak Aktif',
                            ]),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Siswa yang tidak aktif tidak muncul di daftar'),
                    ]),
                    Textarea::make('catatan')
                        ->label('Catatan')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function kesehatanSchema(): array
    {
        return [
            Section::make('Data Kesehatan')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('golongan_darah')
                            ->label('Golongan Darah')
                            ->native(false)
                            ->options([
                                'A' => 'A',
                                'B' => 'B',
                                'AB' => 'AB',
                                'O' => 'O',
                            ]),
                        TextInput::make('tinggi_badan')
                            ->label('Tinggi Badan')
                            ->numeric()
                            ->suffix('cm')
                            ->minValue(50)
                            ->maxValue(250),
                        TextInput::make('berat_badan')
                            ->label('Berat Badan')
                            ->numeric()
                            ->suffix('kg')
                            ->minValue(10)
                            ->maxValue(200),
                    ]),
                    Textarea::make('riwayat_penyakit')
                        ->label('Riwayat Penyakit')
                        ->rows(3)
                        ->placeholder('Tuliskan riwayat penyakit yang pernah diderita')
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function dataAyahSchema(): array
    {
        return [
            Section::make('Data Ayah')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('nama_ayah')
                            ->label('Nama Ayah')
                            ->maxLength(100),
                        TextInput::make('nik_ayah')
                            ->label('NIK Ayah')
                            ->maxLength(20),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('tempat_lahir_ayah')
                            ->label('Tempat Lahir')
                            ->maxLength(50),
                        DatePicker::make('tanggal_lahir_ayah')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ]),
                    Grid::make(3)->schema([
                        Select::make('pendidikan_ayah')
                            ->label('Pendidikan')
                            ->native(false)
                            ->options(self::pendidikanOptions()),
                        TextInput::make('pekerjaan_ayah')
                            ->label('Pekerjaan')
                            ->maxLength(50),
                        TextInput::make('penghasilan_ayah')
                            ->label('Penghasilan')
                            ->numeric()
                            ->prefix('Rp'),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('telepon_ayah')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(20),
                        Textarea::make('alamat_ayah')
                            ->label('Alamat')
                            ->rows(2),
                    ]),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function dataIbuSchema(): array
    {
        return [
            Section::make('Data Ibu')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('nama_ibu')
                            ->label('Nama Ibu')
                            ->maxLength(100),
                        TextInput::make('nik_ibu')
                            ->label('NIK Ibu')
                            ->maxLength(20),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('tempat_lahir_ibu')
                            ->label('Tempat Lahir')
                            ->maxLength(50),
                        DatePicker::make('tanggal_lahir_ibu')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ]),
                    Grid::make(3)->schema([
                        Select::make('pendidikan_ibu')
                            ->label('Pendidikan')
                            ->native(false)
                            ->options(self::pendidikanOptions()),
                        TextInput::make('pekerjaan_ibu')
                            ->label('Pekerjaan')
                            ->maxLength(50),
                        TextInput::make('penghasilan_ibu')
                            ->label('Penghasilan')
                            ->numeric()
                            ->prefix('Rp'),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('telepon_ibu')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(20),
                        Textarea::make('alamat_ibu')
                            ->label('Alamat')
                            ->rows(2),
                    ]),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function dataWaliSchema(): array
    {
        return [
            Section::make('Data Wali')
                ->description('Isi jika wali berbeda dengan orang tua')
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('nama_wali')
                            ->label('Nama Wali')
                            ->maxLength(100),
                        TextInput::make('nik_wali')
                            ->label('NIK Wali')
                            ->maxLength(20),
                        TextInput::make('hubungan_wali')
                            ->label('Hubungan')
                            ->maxLength(30)
                            ->placeholder('Paman, Bibi, dll'),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('tempat_lahir_wali')
                            ->label('Tempat Lahir')
                            ->maxLength(50),
                        DatePicker::make('tanggal_lahir_wali')
                            ->label('Tanggal Lahir')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ]),
                    Grid::make(3)->schema([
                        Select::make('pendidikan_wali')
                            ->label('Pendidikan')
                            ->native(false)
                            ->options(self::pendidikanOptions()),
                        TextInput::make('pekerjaan_wali')
                            ->label('Pekerjaan')
                            ->maxLength(50),
                        TextInput::make('penghasilan_wali')
                            ->label('Penghasilan')
                            ->numeric()
                            ->prefix('Rp'),
                    ]),
                    Grid::make(2)->schema([
                        TextInput::make('telepon_wali')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(20),
                        Textarea::make('alamat_wali')
                            ->label('Alamat')
                            ->rows(2),
                    ]),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Forms\Components\Component>
     */
    private static function dokumenSchema(): array
    {
        return [
            Section::make('Dokumen')
                ->schema([
                    Grid::make(2)->schema([
                        FileUpload::make('foto')
                            ->label('Foto Siswa')
                            ->image()
                            ->directory('siswa/foto')
                            ->imageEditor()
                            ->circleCropper()
                            ->maxSize(2048),
                        FileUpload::make('foto_kk')
                            ->label('Foto Kartu Keluarga')
                            ->image()
                            ->directory('siswa/kk')
                            ->maxSize(2048),
                    ]),
                    Grid::make(2)->schema([
                        FileUpload::make('foto_akta')
                            ->label('Foto Akta Kelahiran')
                            ->image()
                            ->directory('siswa/akta')
                            ->maxSize(2048),
                        FileUpload::make('foto_ijazah')
                            ->label('Foto Ijazah')
                            ->image()
                            ->directory('siswa/ijazah')
                            ->maxSize(2048),
                    ]),
                ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function pendidikanOptions(): array
    {
        return [
            'Tidak Sekolah' => 'Tidak Sekolah',
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
        ];
    }
}
