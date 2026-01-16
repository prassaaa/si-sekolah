<?php

namespace App\Filament\Resources\Akuns\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AkunsTable
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

                TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'aset' => 'info',
                        'liabilitas' => 'warning',
                        'ekuitas' => 'success',
                        'pendapatan' => 'primary',
                        'beban' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('posisi_normal')
                    ->label('Posisi Normal')
                    ->badge()
                    ->color(fn (string $state) => $state === 'debit' ? 'info' : 'warning')
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->toggleable(),

                TextColumn::make('saldo_akhir')
                    ->label('Saldo Akhir')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('level')
                    ->label('Level')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->options([
                        'aset' => 'Aset',
                        'liabilitas' => 'Liabilitas',
                        'ekuitas' => 'Ekuitas',
                        'pendapatan' => 'Pendapatan',
                        'beban' => 'Beban',
                    ]),
                SelectFilter::make('posisi_normal')
                    ->options([
                        'debit' => 'Debit',
                        'kredit' => 'Kredit',
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
