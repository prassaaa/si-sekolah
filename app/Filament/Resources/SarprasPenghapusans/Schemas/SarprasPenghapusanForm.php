<?php

namespace App\Filament\Resources\SarprasPenghapusans\Schemas;

use App\Models\SarprasBarang;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SarprasPenghapusanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Penghapusan')->schema([
                TextInput::make('nomor')
                    ->label('Nomor')
                    ->disabled()
                    ->placeholder('Dibuat otomatis')
                    ->dehydrated(false),

                Select::make('sarpras_barang_id')
                    ->label('Barang / Aset')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(
                        SarprasBarang::query()
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn ($b) => [$b->id => "{$b->kode_inventaris} - {$b->nama}"])
                            ->toArray()
                    ),

                Grid::make(2)->schema([
                    DatePicker::make('tanggal')
                        ->label('Tanggal')
                        ->required()
                        ->default(now())
                        ->native(false),

                    Select::make('alasan')
                        ->label('Alasan')
                        ->required()
                        ->options([
                            'rusak_berat' => 'Rusak Berat',
                            'hilang' => 'Hilang',
                            'usang' => 'Usang',
                            'lainnya' => 'Lainnya',
                        ]),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('jumlah')
                        ->label('Jumlah')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->default(1),

                    TextInput::make('nilai_sisa')
                        ->label('Nilai Sisa (Rp)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                ]),

                Grid::make(2)->schema([
                    Select::make('metode')
                        ->label('Metode Penghapusan')
                        ->required()
                        ->options([
                            'dibuang' => 'Dibuang',
                            'dijual' => 'Dijual',
                            'disumbangkan' => 'Disumbangkan',
                        ]),

                    Select::make('status')
                        ->label('Status')
                        ->required()
                        ->options([
                            'diajukan' => 'Diajukan',
                            'disetujui' => 'Disetujui',
                            'ditolak' => 'Ditolak',
                        ])
                        ->default('diajukan'),
                ]),

                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3),
            ]),
        ]);
    }
}
