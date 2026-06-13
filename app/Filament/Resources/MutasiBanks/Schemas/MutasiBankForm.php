<?php

namespace App\Filament\Resources\MutasiBanks\Schemas;

use App\Filament\Resources\MutasiBanks\MutasiBankResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MutasiBankForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Mutasi Rekening Koran')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('akun_id')
                                ->label('Akun Bank')
                                ->options(fn (): array => MutasiBankResource::bankAkunOptions())
                                ->searchable()
                                ->preload()
                                ->required(),

                            DatePicker::make('tanggal')
                                ->label('Tanggal')
                                ->default(now())
                                ->required(),
                        ]),

                        TextInput::make('keterangan')
                            ->label('Keterangan')
                            ->maxLength(255)
                            ->nullable(),

                        Grid::make(2)->schema([
                            TextInput::make('debit')
                                ->label('Debit (Uang Masuk)')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->minValue(0)
                                ->required(),

                            TextInput::make('kredit')
                                ->label('Kredit (Uang Keluar)')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->minValue(0)
                                ->required(),
                        ]),

                        TextInput::make('saldo')
                            ->label('Saldo Rekening (opsional)')
                            ->numeric()
                            ->prefix('Rp')
                            ->nullable()
                            ->helperText('Saldo akhir baris pada rekening koran, jika dicantumkan.'),
                    ]),
            ]);
    }
}
