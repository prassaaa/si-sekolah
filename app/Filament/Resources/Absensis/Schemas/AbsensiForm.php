<?php

namespace App\Filament\Resources\Absensis\Schemas;

use App\Models\Absensi;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AbsensiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Absensi')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('jadwal_pelajaran_id')->label('Jadwal Pelajaran')
                            ->relationship('jadwalPelajaran', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->jadwal_lengkap)
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('siswa_id')->label('Siswa')
                            ->relationship('siswa', 'nama')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->nama_lengkap)
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                    Grid::make(2)->schema([
                        DatePicker::make('tanggal')->label('Tanggal')
                            ->required()
                            ->default(now()),
                        Select::make('status')->label('Status')
                            ->options(Absensi::statusOptions())
                            ->default('hadir')
                            ->required()
                            ->native(false),
                    ]),
                    Textarea::make('keterangan')->label('Keterangan')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
