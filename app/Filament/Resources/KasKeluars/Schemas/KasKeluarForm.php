<?php

namespace App\Filament\Resources\KasKeluars\Schemas;

use App\Models\Akun;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Schemas\Schema;

class KasKeluarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nomor_bukti')
                    ->label('Nomor Bukti')
                    ->disabled()
                    ->dehydrated()
                    ->placeholder('Auto-generate'),
                Select::make('kas_akun_id')
                    ->label('Akun Kas/Bank')
                    ->options(
                        Akun::query()
                            ->where('tipe', 'aset')
                            ->where(function ($q) {
                                $q->where('kode', 'like', '1-1%')
                                    ->orWhere('nama', 'like', '%Kas%')
                                    ->orWhere('nama', 'like', '%Bank%');
                            })
                            ->where('is_active', true)
                            ->orderBy('kode')
                            ->pluck('nama', 'id')
                    )
                    ->default(function (): ?int {
                        return Akun::query()->where('kode', '1-1001')->value('id');
                    })
                    ->searchable()
                    ->required(),
                Select::make('akun_id')
                    ->relationship('akun', 'nama')
                    ->label('Akun Lawan (Beban)')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rules([
                        fn (Get $get): \Closure => function (string $attribute, mixed $value, \Closure $fail) use ($get) {
                            if ((int) $value === (int) $get('kas_akun_id')) {
                                $fail('Akun lawan tidak boleh sama dengan Akun Kas/Bank yang dipilih.');
                            }
                        },
                    ]),
                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->default(now()),
                TextInput::make('nominal')
                    ->label('Nominal')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(1),
                TextInput::make('penerima')
                    ->label('Penerima')
                    ->maxLength(255),
                Select::make('sumber_dana')
                    ->label('Sumber Dana')
                    ->options([
                        'bos' => 'BOS',
                        'komite' => 'Komite',
                        'yayasan' => 'Yayasan',
                        'lainnya' => 'Lainnya',
                    ])
                    ->default('lainnya')
                    ->required(),
                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
