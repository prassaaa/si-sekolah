<?php

namespace App\Filament\Resources\PeriodeAkuntansis\Schemas;

use App\Models\PeriodeAkuntansi;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PeriodeAkuntansiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Periode')
                    ->description('Tentukan bulan dan tahun periode akuntansi. Periode dibuat dalam keadaan terbuka, lalu dapat ditutup dari daftar.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('tahun')
                                ->label('Tahun')
                                ->options(fn (): array => self::tahunOptions())
                                ->default((int) now()->year)
                                ->required(),

                            Select::make('bulan')
                                ->label('Bulan')
                                ->options(self::bulanOptions())
                                ->default((int) now()->month)
                                ->required(),
                        ]),

                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(2)
                            ->nullable(),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function tahunOptions(): array
    {
        $tahunSekarang = (int) now()->year;
        $options = [];

        for ($tahun = $tahunSekarang - 2; $tahun <= $tahunSekarang + 1; $tahun++) {
            $options[$tahun] = (string) $tahun;
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    private static function bulanOptions(): array
    {
        $options = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $options[$bulan] = PeriodeAkuntansi::namaBulan($bulan);
        }

        return $options;
    }
}
