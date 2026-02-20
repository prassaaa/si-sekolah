<?php

namespace App\Filament\Resources\Pembayarans\Schemas;

use App\Models\Pegawai;
use App\Models\TagihanSiswa;
use App\Models\UnitPos;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PembayaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Pembayaran')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('tagihan_siswa_id')
                                ->label('Tagihan')
                                ->relationship('tagihanSiswa', 'nomor_tagihan', fn ($query) => $query->where('status', '!=', 'lunas')->where('status', '!=', 'batal'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, ?string $state) {
                                    if ($state) {
                                        $tagihan = TagihanSiswa::with('siswa', 'jenisPembayaran')->find($state);
                                        if ($tagihan) {
                                            $set('jumlah_bayar', $tagihan->sisa_tagihan);
                                        }
                                    }
                                })
                                ->getOptionLabelFromRecordUsing(fn (TagihanSiswa $record) => "{$record->nomor_tagihan} - {$record->siswa->nama} (Sisa: Rp ".number_format($record->sisa_tagihan, 0, ',', '.').')'),

                            TextInput::make('nomor_transaksi')
                                ->label('Nomor Transaksi')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(50)
                                ->default(fn () => 'PAY-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6))),
                        ]),

                        Placeholder::make('tagihan_info')
                            ->label('Informasi Tagihan')
                            ->content(function ($get) {
                                $tagihanId = $get('tagihan_siswa_id');
                                if (! $tagihanId) {
                                    return 'Pilih tagihan terlebih dahulu';
                                }
                                $tagihan = TagihanSiswa::with('siswa', 'jenisPembayaran')->find($tagihanId);
                                if (! $tagihan) {
                                    return '-';
                                }

                                return "Siswa: {$tagihan->siswa->nama} | Jenis: {$tagihan->jenisPembayaran->nama} | Total: Rp ".number_format($tagihan->total_tagihan, 0, ',', '.').' | Sisa: Rp '.number_format($tagihan->sisa_tagihan, 0, ',', '.');
                            })
                            ->hiddenOn('edit'),
                    ]),

                Section::make('Detail Pembayaran')
                    ->schema([
                        Grid::make(2)->schema([
                            DatePicker::make('tanggal_bayar')
                                ->label('Tanggal Bayar')
                                ->required()
                                ->default(now())
                                ->native(false),

                            TextInput::make('jumlah_bayar')
                                ->label('Jumlah Bayar')
                                ->required()
                                ->numeric()
                                ->prefix('Rp')
                                ->minValue(1),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('metode_pembayaran')
                                ->label('Metode Pembayaran')
                                ->options([
                                    'tunai' => 'Tunai',
                                    'transfer' => 'Transfer Bank',
                                    'qris' => 'QRIS',
                                    'virtual_account' => 'Virtual Account',
                                    'lainnya' => 'Lainnya',
                                ])
                                ->required()
                                ->default('tunai'),

                            TextInput::make('referensi_pembayaran')
                                ->label('Referensi Pembayaran')
                                ->maxLength(100)
                                ->helperText('No. rekening, ID transaksi, dll'),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('diterima_oleh')
                                ->label('Diterima Oleh')
                                ->options(Pegawai::query()->where('is_active', true)->pluck('nama', 'id'))
                                ->searchable()
                                ->default(fn () => Pegawai::where('user_id', Auth::id())->value('id')),

                            Select::make('unit_pos_id')
                                ->label('Unit POS')
                                ->options(UnitPos::query()->where('is_active', true)->pluck('nama', 'id'))
                                ->searchable()
                                ->placeholder('Pilih Unit POS'),
                        ]),

                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'berhasil' => 'Berhasil',
                                    'gagal' => 'Gagal',
                                    'batal' => 'Batal',
                                ])
                                ->default('berhasil')
                                ->required(),
                        ]),

                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3),
                    ]),
            ]);
    }
}
