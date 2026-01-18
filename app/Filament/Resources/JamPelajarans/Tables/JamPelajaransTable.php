<?php

namespace App\Filament\Resources\JamPelajarans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class JamPelajaransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jam_ke')
                    ->label('Jam Ke')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => "Jam {$state}")
                    ->weight('bold'),
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Reguler' => 'primary',
                        'Istirahat' => 'warning',
                        'Upacara' => 'info',
                        'Ekstrakurikuler' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('waktu_mulai')
                    ->label('Mulai')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('waktu_selesai')
                    ->label('Selesai')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('durasi')
                    ->label('Durasi')
                    ->suffix(' menit')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->toggleable(),
                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->sortable(),
            ])
            ->defaultSort('jam_ke', 'asc')
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options([
                        'Reguler' => 'Reguler',
                        'Istirahat' => 'Istirahat',
                        'Upacara' => 'Upacara',
                        'Ekstrakurikuler' => 'Ekstrakurikuler',
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
