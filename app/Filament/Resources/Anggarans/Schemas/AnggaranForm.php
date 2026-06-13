<?php

namespace App\Filament\Resources\Anggarans\Schemas;

use App\Models\Akun;
use App\Models\TahunAjaran;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class AnggaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Anggaran')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('tahun_ajaran_id')
                                ->label('Tahun Ajaran')
                                ->options(
                                    TahunAjaran::query()
                                        ->orderByDesc('tanggal_mulai')
                                        ->pluck('nama', 'id')
                                )
                                ->default(fn () => TahunAjaran::getActive()?->id)
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live(),

                            Select::make('akun_id')
                                ->label('Akun')
                                ->options(
                                    Akun::query()
                                        ->whereIn('tipe', ['pendapatan', 'beban'])
                                        ->where('is_active', true)
                                        ->orderBy('kode')
                                        ->get()
                                        ->mapWithKeys(fn (Akun $akun) => [$akun->id => "{$akun->kode} - {$akun->nama}"])
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->rules(function ($get, $record) {
                                    return [
                                        Rule::unique('anggarans', 'akun_id')
                                            ->where('tahun_ajaran_id', $get('tahun_ajaran_id'))
                                            ->whereNull('deleted_at')
                                            ->ignore($record?->id),
                                    ];
                                })
                                ->validationMessages([
                                    'unique' => 'Akun ini sudah memiliki anggaran untuk tahun ajaran tersebut.',
                                ]),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('nominal_anggaran')
                                ->label('Nominal Anggaran')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->required()
                                ->minValue(0),

                            Textarea::make('keterangan')
                                ->label('Keterangan')
                                ->rows(2)
                                ->nullable(),
                        ]),
                    ]),
            ]);
    }
}
