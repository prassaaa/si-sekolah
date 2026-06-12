<?php

namespace App\Filament\Resources\JurnalUmums\Schemas;

use App\Models\Akun;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Skema form CREATE jurnal manual — double-entry (multi-baris).
 * Header berisi tanggal + keterangan; detail adalah Repeater minimal 2 baris
 * (satu akun per baris, debit XOR kredit). Validasi total D = K dilakukan di
 * server saat handleRecordCreation.
 */
class JurnalUmumCreateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Header Jurnal')->schema([
                Grid::make(2)->schema([
                    DatePicker::make('tanggal')
                        ->label('Tanggal')
                        ->required()
                        ->default(now())
                        ->native(false),

                    Placeholder::make('nomor_bukti_placeholder')
                        ->label('Nomor Bukti')
                        ->content('Otomatis'),
                ]),

                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(2),
            ]),

            Section::make('Detail Baris Jurnal')->schema([
                Repeater::make('details')
                    ->label('Baris Jurnal')
                    ->schema([
                        Select::make('akun_id')
                            ->label('Akun')
                            ->relationship(
                                'akun',
                                'nama',
                                fn ($query) => $query->active(),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->getOptionLabelFromRecordUsing(
                                fn (Akun $record) => "{$record->kode} - {$record->nama}",
                            ),

                        Grid::make(2)->schema([
                            TextInput::make('debit')
                                ->label('Debit')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->minValue(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $set): void {
                                    if ($state > 0) {
                                        $set('kredit', 0);
                                    }
                                }),

                            TextInput::make('kredit')
                                ->label('Kredit')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->minValue(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, $set): void {
                                    if ($state > 0) {
                                        $set('debit', 0);
                                    }
                                }),
                        ]),
                    ])
                    ->columns(1)
                    ->defaultItems(2)
                    ->minItems(2)
                    ->addActionLabel('Tambah Baris')
                    ->reorderable(false)
                    ->live(onBlur: true),

                Grid::make(3)->schema([
                    Placeholder::make('total_debit_display')
                        ->label('Total Debit')
                        ->live()
                        ->content(function (Get $get): string {
                            $total = collect($get('details') ?? [])
                                ->sum(fn ($row) => floatval($row['debit'] ?? 0));

                            return 'Rp '.number_format($total, 2, ',', '.');
                        }),

                    Placeholder::make('total_kredit_display')
                        ->label('Total Kredit')
                        ->live()
                        ->content(function (Get $get): string {
                            $total = collect($get('details') ?? [])
                                ->sum(fn ($row) => floatval($row['kredit'] ?? 0));

                            return 'Rp '.number_format($total, 2, ',', '.');
                        }),

                    Placeholder::make('selisih_display')
                        ->label('Selisih')
                        ->live()
                        ->content(function (Get $get): string {
                            $debit = collect($get('details') ?? [])
                                ->sum(fn ($row) => floatval($row['debit'] ?? 0));
                            $kredit = collect($get('details') ?? [])
                                ->sum(fn ($row) => floatval($row['kredit'] ?? 0));
                            $selisih = $debit - $kredit;

                            return 'Rp '.number_format($selisih, 2, ',', '.');
                        }),
                ]),
            ]),
        ]);
    }
}
