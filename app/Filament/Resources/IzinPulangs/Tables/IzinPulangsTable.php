<?php

namespace App\Filament\Resources\IzinPulangs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IzinPulangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('siswa.nis')
                    ->label('NIS')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('jam_pulang')
                    ->label('Jam Pulang')
                    ->time('H:i')
                    ->sortable(),

                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'sakit' => 'Sakit',
                        'kepentingan_keluarga' => 'Keluarga',
                        'urusan_pribadi' => 'Pribadi',
                        'lainnya' => 'Lainnya',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'sakit' => 'danger',
                        'kepentingan_keluarga' => 'info',
                        'urusan_pribadi' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('alasan')
                    ->label('Alasan')
                    ->limit(30)
                    ->searchable(),

                TextColumn::make('penjemput_nama')
                    ->label('Penjemput')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'diizinkan' => 'Diizinkan',
                        'ditolak' => 'Ditolak',
                        'pending' => 'Pending',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'diizinkan' => 'success',
                        'ditolak' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('petugas.nama')
                    ->label('Petugas')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'diizinkan' => 'Diizinkan',
                        'ditolak' => 'Ditolak',
                        'pending' => 'Pending',
                    ]),

                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options([
                        'sakit' => 'Sakit',
                        'kepentingan_keluarga' => 'Kepentingan Keluarga',
                        'urusan_pribadi' => 'Urusan Pribadi',
                        'lainnya' => 'Lainnya',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal', 'desc');
    }
}
