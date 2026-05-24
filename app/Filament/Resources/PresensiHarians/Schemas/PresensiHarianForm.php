<?php

namespace App\Filament\Resources\PresensiHarians\Schemas;

use App\Models\PresensiHarian;
use App\Models\Siswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PresensiHarianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Siswa')->schema([
                Grid::make(2)->schema([
                    Select::make('siswa_id')
                        ->label('Siswa')
                        ->relationship('siswa', 'nama')
                        ->getOptionLabelFromRecordUsing(
                            fn (Siswa $record) => "{$record->nis} - {$record->nama}"
                        )
                        ->searchable(['nis', 'nama'])
                        ->preload()
                        ->required(),

                    DatePicker::make('tanggal')
                        ->label('Tanggal')
                        ->required()
                        ->native(false)
                        ->default(now()),
                ]),
            ]),

            Section::make('Kehadiran')->schema([
                Grid::make(2)->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options(PresensiHarian::statusOptions())
                        ->required()
                        ->default('hadir')
                        ->live(),

                    TextInput::make('terlambat_menit')
                        ->label('Terlambat (menit)')
                        ->numeric()
                        ->minValue(0)
                        ->visible(fn ($get) => $get('status') === 'terlambat'),
                ]),

                Grid::make(2)->schema([
                    TimePicker::make('jam_masuk')
                        ->label('Jam Masuk')
                        ->seconds(false)
                        ->visible(fn ($get) => in_array($get('status'), ['hadir', 'terlambat'])),

                    TimePicker::make('jam_pulang')
                        ->label('Jam Pulang')
                        ->seconds(false)
                        ->visible(fn ($get) => in_array($get('status'), ['hadir', 'terlambat'])),
                ]),
            ]),

            Section::make('Sumber Data')->schema([
                Grid::make(2)->schema([
                    Select::make('sumber_masuk')
                        ->label('Sumber Tap Masuk')
                        ->options(PresensiHarian::sumberOptions())
                        ->default('manual'),

                    Select::make('sumber_pulang')
                        ->label('Sumber Tap Pulang')
                        ->options(PresensiHarian::sumberOptions()),
                ]),

                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3)
                    ->placeholder('Catatan tambahan (mis. surat dokter, izin keluarga, dst)'),
            ]),
        ]);
    }
}
