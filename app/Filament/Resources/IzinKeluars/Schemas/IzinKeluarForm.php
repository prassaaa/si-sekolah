<?php

namespace App\Filament\Resources\IzinKeluars\Schemas;

use App\Models\Siswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class IzinKeluarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Izin')
                    ->schema([
                        Select::make('siswa_id')
                            ->label('Siswa')
                            ->relationship('siswa', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(fn (Siswa $record) => "{$record->nis} - {$record->nama}"),

                        Grid::make(2)->schema([
                            DatePicker::make('tanggal')
                                ->label('Tanggal')
                                ->required()
                                ->default(now())
                                ->native(false),

                            TimePicker::make('jam_keluar')
                                ->label('Jam Keluar')
                                ->required()
                                ->seconds(false)
                                ->native(false),
                        ]),

                        TimePicker::make('jam_kembali')
                            ->label('Jam Kembali')
                            ->seconds(false)
                            ->native(false),

                        TextInput::make('keperluan')
                            ->label('Keperluan')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('tujuan')
                            ->label('Tujuan')
                            ->maxLength(255),
                    ]),

                Section::make('Data Penjemput')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('penjemput_nama')
                                ->label('Nama Penjemput')
                                ->maxLength(100),

                            TextInput::make('penjemput_hubungan')
                                ->label('Hubungan')
                                ->maxLength(50)
                                ->placeholder('Ayah, Ibu, Wali, dll'),

                            TextInput::make('penjemput_telepon')
                                ->label('Telepon')
                                ->tel()
                                ->maxLength(20),
                        ]),
                    ])
                    ->collapsible(),

                Section::make('Status & Catatan')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('petugas_id')
                                ->label('Petugas')
                                ->relationship('petugas', 'nama')
                                ->searchable()
                                ->preload(),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'diizinkan' => 'Diizinkan',
                                    'ditolak' => 'Ditolak',
                                ])
                                ->required()
                                ->default('pending'),
                        ]),

                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
