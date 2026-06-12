<?php

namespace App\Filament\Resources\SlipGajis\Schemas;

use App\Models\SettingGaji;
use App\Models\SlipGaji;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SlipGajiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Slip Gaji')
                    ->schema([
                        TextInput::make('nomor')
                            ->label('Nomor Slip')
                            ->disabled()
                            ->dehydrated()
                            ->placeholder('Auto-generated'),
                        Select::make('pegawai_id')
                            ->label('Pegawai')
                            ->relationship('pegawai', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $setting = SettingGaji::where('pegawai_id', $state)
                                    ->where('is_active', true)
                                    ->first();
                                if ($setting) {
                                    $set('setting_gaji_id', $setting->id);
                                    $set('gaji_pokok', $setting->gaji_pokok);
                                    $set('total_tunjangan', $setting->total_tunjangan);
                                    $set('total_potongan', $setting->total_potongan);
                                    $set('gaji_bersih', $setting->gaji_bersih);
                                    $set('detail_tunjangan', [
                                        'jabatan' => $setting->tunjangan_jabatan,
                                        'kehadiran' => $setting->tunjangan_kehadiran,
                                        'transport' => $setting->tunjangan_transport,
                                        'makan' => $setting->tunjangan_makan,
                                        'lainnya' => $setting->tunjangan_lainnya,
                                    ]);
                                    $set('detail_potongan', [
                                        'bpjs' => $setting->potongan_bpjs,
                                        'pph21' => $setting->potongan_pph21,
                                        'lainnya' => $setting->potongan_lainnya,
                                    ]);
                                }
                            }),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('tahun')
                                    ->label('Tahun')
                                    ->numeric()
                                    ->default(date('Y'))
                                    ->required()
                                    ->minValue(2000)
                                    ->maxValue((int) date('Y') + 1),
                                Select::make('bulan')
                                    ->label('Bulan')
                                    ->options([
                                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                                        4 => 'April', 5 => 'Mei', 6 => 'Juni',
                                        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                                        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                                    ])
                                    ->default((int) date('m'))
                                    ->required(),
                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'approved' => 'Approved',
                                        'paid' => 'Paid',
                                    ])
                                    ->default('draft')
                                    ->required(),
                            ]),
                    ])->columns(2),

                Section::make('Rincian Gaji')
                    ->schema([
                        TextInput::make('setting_gaji_id')
                            ->hidden(),
                        TextInput::make('gaji_pokok')
                            ->label('Gaji Pokok')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('total_tunjangan')
                            ->label('Total Tunjangan')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('total_potongan')
                            ->label('Total Potongan')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('gaji_bersih')
                            ->label('Gaji Bersih')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(4),

                Section::make('Pembayaran')
                    ->schema([
                        DatePicker::make('tanggal_bayar')
                            ->label('Tanggal Bayar'),
                        Textarea::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ]),

                Hidden::make('detail_tunjangan')->dehydrated(),
                Hidden::make('detail_potongan')->dehydrated(),

                TextInput::make('_duplicate_guard')
                    ->hidden()
                    ->dehydrated(false)
                    ->rule(fn (Get $get) => function ($attribute, $value, $fail) use ($get) {
                        $pegawaiId = $get('pegawai_id');
                        $tahun = $get('tahun');
                        $bulan = $get('bulan');

                        if (! $pegawaiId || ! $tahun || ! $bulan) {
                            return;
                        }

                        $exists = SlipGaji::query()
                            ->where('pegawai_id', $pegawaiId)
                            ->where('tahun', $tahun)
                            ->where('bulan', $bulan)
                            ->when(
                                request()->route('record'),
                                fn ($q) => $q->where('id', '!=', request()->route('record'))
                            )
                            ->exists();

                        if ($exists) {
                            $fail('Slip gaji untuk pegawai, tahun, dan bulan ini sudah ada.');
                        }
                    }),
            ]);
    }
}
