<?php

namespace App\Filament\Resources\Pelanggarans\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PelanggaransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('jenis_pelanggaran')
                    ->label('Jenis Pelanggaran')
                    ->searchable()
                    ->wrap()
                    ->limit(50),

                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'ringan' => 'Ringan',
                        'sedang' => 'Sedang',
                        'berat' => 'Berat',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'ringan' => 'info',
                        'sedang' => 'warning',
                        'berat' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('poin')
                    ->label('Poin')
                    ->badge()
                    ->color('danger'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'proses' => 'Proses',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'proses' => 'warning',
                        'selesai' => 'success',
                        'batal' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('pelapor.nama')
                    ->label('Pelapor')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->options([
                        'ringan' => 'Ringan',
                        'sedang' => 'Sedang',
                        'berat' => 'Berat',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'proses' => 'Dalam Proses',
                        'selesai' => 'Selesai',
                        'batal' => 'Dibatalkan',
                    ]),
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
            ->defaultSort('tanggal', 'desc');
    }
}
