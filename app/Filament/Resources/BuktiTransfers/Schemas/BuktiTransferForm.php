<?php

namespace App\Filament\Resources\BuktiTransfers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BuktiTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Transfer')
                    ->schema([
                        Select::make('siswa_id')
                            ->relationship('siswa', 'nama')
                            ->label('Siswa')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('tagihan_siswa_id')
                            ->relationship('tagihanSiswa', 'id')
                            ->label('Tagihan (Opsional)')
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} - Rp ".number_format($record->nominal, 0, ',', '.')),
                        TextInput::make('nama_pengirim')
                            ->label('Nama Pengirim')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('bank_pengirim')
                            ->label('Bank Pengirim')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('nomor_rekening')
                            ->label('Nomor Rekening')
                            ->maxLength(50),
                        TextInput::make('nominal')
                            ->label('Nominal')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(1),
                        DatePicker::make('tanggal_transfer')
                            ->label('Tanggal Transfer')
                            ->required()
                            ->default(now()),
                    ])->columns(2),
                Section::make('Bukti & Catatan')
                    ->schema([
                        FileUpload::make('bukti_file')
                            ->label('File Bukti Transfer')
                            ->image()
                            ->directory('bukti-transfer')
                            ->maxSize(2048),
                        Textarea::make('catatan_wali')
                            ->label('Catatan dari Wali Murid')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Verifikasi')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'verified' => 'Terverifikasi',
                                'rejected' => 'Ditolak',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),
                        Textarea::make('catatan_admin')
                            ->label('Catatan Admin')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
