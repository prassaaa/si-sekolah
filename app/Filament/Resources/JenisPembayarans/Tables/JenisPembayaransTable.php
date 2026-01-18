<?php

namespace App\Filament\Resources\JenisPembayarans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class JenisPembayaransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kategoriPembayaran.nama')
                    ->label('Kategori')
                    ->sortable(),

                TextColumn::make('tahunAjaran.nama')
                    ->label('Tahun Ajaran')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'bulanan' => 'Bulanan',
                        'tahunan' => 'Tahunan',
                        'sekali_bayar' => 'Sekali Bayar',
                        'insidental' => 'Insidental',
                        default => $state,
                    }),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('kategori_pembayaran_id')
                    ->label('Kategori')
                    ->relationship('kategoriPembayaran', 'nama'),
                SelectFilter::make('jenis')
                    ->options([
                        'bulanan' => 'Bulanan',
                        'tahunan' => 'Tahunan',
                        'sekali_bayar' => 'Sekali Bayar',
                        'insidental' => 'Insidental',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
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
            ->defaultSort('kode');
    }
}
