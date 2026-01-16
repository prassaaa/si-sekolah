<?php

namespace App\Filament\Resources\TagihanSiswas\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TagihanSiswasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_tagihan')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('jenisPembayaran.nama')
                    ->label('Jenis Pembayaran')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_tagihan')
                    ->label('Total Tagihan')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('total_terbayar')
                    ->label('Terbayar')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('sisa_tagihan')
                    ->label('Sisa')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'belum_bayar' => 'danger',
                        'sebagian' => 'warning',
                        'lunas' => 'success',
                        'batal' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'belum_bayar' => 'Belum Bayar',
                        'sebagian' => 'Sebagian',
                        'lunas' => 'Lunas',
                        'batal' => 'Batal',
                        default => $state,
                    }),

                TextColumn::make('tanggal_jatuh_tempo')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'belum_bayar' => 'Belum Bayar',
                        'sebagian' => 'Sebagian',
                        'lunas' => 'Lunas',
                        'batal' => 'Batal',
                    ]),
                SelectFilter::make('jenis_pembayaran_id')
                    ->label('Jenis Pembayaran')
                    ->relationship('jenisPembayaran', 'nama'),
                SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->relationship('semester', 'nama'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal_tagihan', 'desc');
    }
}
