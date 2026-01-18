<?php

namespace App\Filament\Resources\MataPelajarans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MataPelajaransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('nama')
                    ->label('Nama Mata Pelajaran')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): ?string => $record->singkatan),
                TextColumn::make('kelompok')
                    ->label('Kelompok')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('jenjang')
                    ->label('Jenjang')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('jam_per_minggu')
                    ->label('Jam/Minggu')
                    ->suffix(' jam')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('kkm')
                    ->label('KKM')
                    ->badge()
                    ->color('warning')
                    ->sortable()
                    ->alignCenter(),
                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->sortable(),
            ])
            ->defaultSort('urutan', 'asc')
            ->reorderable('urutan')
            ->filters([
                SelectFilter::make('kelompok')
                    ->label('Kelompok')
                    ->options([
                        'Kelompok A' => 'Kelompok A',
                        'Kelompok B' => 'Kelompok B',
                        'Kelompok C' => 'Kelompok C',
                        'Muatan Lokal' => 'Muatan Lokal',
                    ]),
                SelectFilter::make('jenjang')
                    ->label('Jenjang')
                    ->options([
                        'TK' => 'TK',
                        'SD' => 'SD',
                        'SMP' => 'SMP',
                        'SMA' => 'SMA',
                        'SMK' => 'SMK',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Non-Aktif'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
