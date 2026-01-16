<?php

namespace App\Filament\Resources\Kelulusans\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KelulusansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('siswa.nisn')
                    ->label('NISN')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tahunAjaran.nama')
                    ->label('Tahun Ajaran')
                    ->sortable(),

                TextColumn::make('tanggal_lulus')
                    ->label('Tanggal Lulus')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'lulus' => 'Lulus',
                        'tidak_lulus' => 'Tidak Lulus',
                        'pending' => 'Pending',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'lulus' => 'success',
                        'tidak_lulus' => 'danger',
                        'pending' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('predikat')
                    ->label('Predikat')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'sangat_baik' => 'Sangat Baik',
                        'baik' => 'Baik',
                        'cukup' => 'Cukup',
                        'kurang' => 'Kurang',
                        default => '-',
                    })
                    ->toggleable(),

                TextColumn::make('nilai_akhir')
                    ->label('Nilai')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('nomor_ijazah')
                    ->label('No. Ijazah')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'lulus' => 'Lulus',
                        'tidak_lulus' => 'Tidak Lulus',
                        'pending' => 'Pending',
                    ]),
                SelectFilter::make('predikat')
                    ->options([
                        'sangat_baik' => 'Sangat Baik',
                        'baik' => 'Baik',
                        'cukup' => 'Cukup',
                        'kurang' => 'Kurang',
                    ]),
                SelectFilter::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->relationship('tahunAjaran', 'nama'),
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
            ->defaultSort('tanggal_lulus', 'desc');
    }
}
