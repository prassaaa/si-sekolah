<?php

namespace App\Filament\Resources\Aduans\Schemas;

use App\Models\Pegawai;
use App\Models\Siswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AduanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pelapor')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('pelapor')
                                ->label('Nama Pelapor')
                                ->required()
                                ->maxLength(255),

                            Select::make('hubungan_pelapor')
                                ->label('Hubungan Pelapor')
                                ->options([
                                    'siswa' => 'Siswa',
                                    'ayah' => 'Ayah',
                                    'ibu' => 'Ibu',
                                    'wali' => 'Wali',
                                    'lainnya' => 'Lainnya',
                                ])
                                ->default('lainnya')
                                ->required(),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('kontak_pelapor')
                                ->label('Kontak Pelapor')
                                ->placeholder('Nomor HP / Email')
                                ->maxLength(255),

                            Select::make('siswa_id')
                                ->label('Siswa Terkait')
                                ->relationship('siswa', 'nama')
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->getOptionLabelFromRecordUsing(fn (Siswa $record) => "{$record->nis} - {$record->nama}"),
                        ]),

                        DatePicker::make('tanggal_aduan')
                            ->label('Tanggal Aduan')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        Hidden::make('dicatat_oleh')
                            ->default(fn () => auth()->id())
                            ->dehydrated(),
                    ]),

                Section::make('Isi Aduan')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('kategori')
                                ->label('Kategori')
                                ->options([
                                    'akademik' => 'Akademik',
                                    'fasilitas' => 'Fasilitas',
                                    'perlakuan' => 'Perlakuan',
                                    'keuangan' => 'Keuangan',
                                    'lainnya' => 'Lainnya',
                                ])
                                ->default('lainnya')
                                ->required(),

                            TextInput::make('judul')
                                ->label('Judul Aduan')
                                ->required()
                                ->maxLength(255),
                        ]),

                        Textarea::make('isi')
                            ->label('Isi Aduan')
                            ->required()
                            ->rows(4),

                        FileUpload::make('lampiran')
                            ->label('Lampiran')
                            ->directory('aduan-lampiran')
                            ->nullable(),
                    ]),

                Section::make('Penanganan')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'baru' => 'Baru',
                                    'diproses' => 'Diproses',
                                    'selesai' => 'Selesai',
                                    'ditolak' => 'Ditolak',
                                ])
                                ->default('baru')
                                ->required(),

                            Select::make('ditangani_oleh')
                                ->label('Ditangani Oleh')
                                ->options(fn () => Pegawai::active()->pluck('nama', 'id'))
                                ->searchable()
                                ->preload(),
                        ]),

                        Textarea::make('tanggapan')
                            ->label('Tanggapan')
                            ->rows(4),

                        DateTimePicker::make('tanggal_tanggapan')
                            ->label('Tanggal Tanggapan')
                            ->native(false),
                    ])
                    ->hiddenOn('create'),
            ]);
    }
}
