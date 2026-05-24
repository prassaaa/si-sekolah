<?php

namespace App\Filament\Resources\PresensiHarianPegawais\Schemas;

use App\Models\Pegawai;
use App\Models\PresensiHarianPegawai;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PresensiHarianPegawaiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas Pegawai')->schema([
                Grid::make(2)->schema([
                    Select::make('pegawai_id')
                        ->label('Pegawai')
                        ->relationship('pegawai', 'nama')
                        ->getOptionLabelFromRecordUsing(
                            fn (Pegawai $record) => "{$record->nip} - {$record->nama}"
                        )
                        ->searchable(['nip', 'nama'])
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
                        ->options(PresensiHarianPegawai::statusOptions())
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
                        ->options(PresensiHarianPegawai::sumberOptions())
                        ->default('manual'),

                    Select::make('sumber_pulang')
                        ->label('Sumber Tap Pulang')
                        ->options(PresensiHarianPegawai::sumberOptions()),
                ]),

                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3)
                    ->placeholder('Catatan tambahan (mis. cuti, dinas luar, dst)'),
            ]),
        ]);
    }
}
