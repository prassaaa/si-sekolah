<?php

namespace App\Filament\Widgets;

use App\Models\TagihanSiswa;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TagihanBelumLunasWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Tagihan Belum Lunas (Jatuh Tempo Terdekat)';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                TagihanSiswa::query()
                    ->with(['siswa', 'jenisPembayaran'])
                    ->whereIn('status', ['belum_bayar', 'sebagian'])
                    ->orderBy('tanggal_jatuh_tempo', 'asc')
            )
            ->columns([
                TextColumn::make('nomor_tagihan')
                    ->label('No. Tagihan')
                    ->searchable(),

                TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->searchable(),

                TextColumn::make('jenisPembayaran.nama')
                    ->label('Jenis'),

                TextColumn::make('sisa_tagihan')
                    ->label('Sisa Tagihan')
                    ->money('IDR')
                    ->color('danger'),

                TextColumn::make('tanggal_jatuh_tempo')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->color(fn ($record) => $record->tanggal_jatuh_tempo && $record->tanggal_jatuh_tempo < now() ? 'danger' : 'warning'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'belum_bayar' => 'danger',
                        'sebagian' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'belum_bayar' => 'Belum Bayar',
                        'sebagian' => 'Sebagian',
                        default => $state,
                    }),
            ])
            ->defaultPaginationPageOption(5);
    }
}
