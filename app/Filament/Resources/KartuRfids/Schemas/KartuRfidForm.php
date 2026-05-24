<?php

namespace App\Filament\Resources\KartuRfids\Schemas;

use App\Models\Pegawai;
use App\Models\Siswa;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KartuRfidForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pemilik Kartu')->schema([
                Select::make('owner_type')
                    ->label('Tipe Pemilik')
                    ->options([
                        Siswa::class => 'Siswa',
                        Pegawai::class => 'Pegawai',
                    ])
                    ->required()
                    ->live()
                    ->default(Siswa::class)
                    ->afterStateUpdated(fn ($set) => $set('owner_id', null)),

                Select::make('owner_id')
                    ->label('Pemilik')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(function ($get) {
                        $type = $get('owner_type');

                        if ($type === Pegawai::class) {
                            return Pegawai::query()
                                ->where('is_active', true)
                                ->get()
                                ->mapWithKeys(fn ($p) => [$p->id => "{$p->nip} - {$p->nama}"])
                                ->toArray();
                        }

                        return Siswa::query()
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn ($s) => [$s->id => "{$s->nis} - {$s->nama}"])
                            ->toArray();
                    }),
            ]),

            Section::make('Data Kartu')->schema([
                TextInput::make('uid')
                    ->label('UID Kartu')
                    ->required()
                    ->maxLength(32)
                    ->placeholder('04A1B2C3 (hex uppercase tanpa separator)')
                    ->helperText('Otomatis dinormalisasi ke uppercase tanpa separator')
                    ->unique(ignoreRecord: true),

                Grid::make(2)->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'aktif' => 'Aktif',
                            'nonaktif' => 'Nonaktif',
                            'hilang' => 'Hilang',
                            'rusak' => 'Rusak',
                        ])
                        ->default('aktif')
                        ->required()
                        ->live(),

                    DateTimePicker::make('diaktifkan_pada')
                        ->label('Diaktifkan Pada')
                        ->required()
                        ->default(now())
                        ->native(false),
                ]),

                DateTimePicker::make('dinonaktifkan_pada')
                    ->label('Dinonaktifkan Pada')
                    ->native(false)
                    ->visible(fn ($get) => in_array($get('status'), ['nonaktif', 'hilang', 'rusak'])),

                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3),
            ]),
        ]);
    }
}
