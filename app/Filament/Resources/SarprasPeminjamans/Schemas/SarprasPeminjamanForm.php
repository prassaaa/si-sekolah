<?php

namespace App\Filament\Resources\SarprasPeminjamans\Schemas;

use App\Models\Pegawai;
use App\Models\SarprasBarang;
use App\Models\Siswa;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SarprasPeminjamanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Barang')->schema([
                Select::make('sarpras_barang_id')
                    ->label('Barang / Aset')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        return SarprasBarang::query()
                            ->where('status', 'tersedia')
                            ->where('is_active', true)
                            ->get()
                            ->mapWithKeys(fn ($b) => [$b->id => "{$b->kode_inventaris} - {$b->nama}"])
                            ->toArray();
                    })
                    ->validationMessages([
                        'required' => 'Barang wajib dipilih.',
                    ]),

                TextInput::make('jumlah')
                    ->label('Jumlah')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->default(1),
            ]),

            Section::make('Peminjam')->schema([
                Select::make('peminjam_type')
                    ->label('Tipe Peminjam')
                    ->options([
                        Siswa::class => 'Siswa',
                        Pegawai::class => 'Pegawai',
                    ])
                    ->required()
                    ->live()
                    ->default(Siswa::class)
                    ->afterStateUpdated(fn ($set) => $set('peminjam_id', null)),

                Select::make('peminjam_id')
                    ->label('Peminjam')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(function ($get) {
                        $type = $get('peminjam_type');

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

            Section::make('Tanggal & Kondisi')->schema([
                Grid::make(2)->schema([
                    DatePicker::make('tanggal_pinjam')
                        ->label('Tanggal Pinjam')
                        ->required()
                        ->default(now())
                        ->native(false),

                    DatePicker::make('tanggal_harus_kembali')
                        ->label('Tanggal Harus Kembali')
                        ->required()
                        ->native(false)
                        ->afterOrEqual('tanggal_pinjam'),
                ]),

                Select::make('kondisi_pinjam')
                    ->label('Kondisi Saat Dipinjam')
                    ->required()
                    ->options([
                        'baik' => 'Baik',
                        'rusak_ringan' => 'Rusak Ringan',
                        'rusak_berat' => 'Rusak Berat',
                    ])
                    ->default('baik'),
            ]),

            Section::make('Petugas & Catatan')->schema([
                Select::make('petugas_id')
                    ->label('Petugas')
                    ->searchable()
                    ->preload()
                    ->options(fn () => Pegawai::query()
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn ($p) => [$p->id => "{$p->nip} - {$p->nama}"])
                        ->toArray()),

                Textarea::make('catatan')
                    ->label('Catatan')
                    ->rows(3),
            ]),
        ]);
    }
}
