<?php

namespace App\Filament\Resources\KenaikanKelass\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KenaikanKelassTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('semester.nama')
                    ->label('Semester')
                    ->formatStateUsing(fn ($state, $record) => $record->semester?->tahunAjaran?->nama.' - '.$state)
                    ->toggleable(),

                TextColumn::make('kelasAsal.nama')
                    ->label('Kelas Asal')
                    ->sortable(),

                TextColumn::make('kelasTujuan.nama')
                    ->label('Kelas Tujuan')
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'naik' => 'Naik',
                        'tinggal' => 'Tinggal',
                        'mutasi_keluar' => 'Mutasi',
                        'pending' => 'Pending',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'naik' => 'success',
                        'tinggal' => 'danger',
                        'mutasi_keluar' => 'warning',
                        'pending' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('nilai_rata_rata')
                    ->label('Nilai')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('peringkat')
                    ->label('Ranking')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tanggal_keputusan')
                    ->label('Tgl Keputusan')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'naik' => 'Naik Kelas',
                        'tinggal' => 'Tinggal Kelas',
                        'mutasi_keluar' => 'Mutasi Keluar',
                        'pending' => 'Pending',
                    ]),
                SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->relationship('semester', 'nama'),
                SelectFilter::make('kelas_asal_id')
                    ->label('Kelas Asal')
                    ->relationship('kelasAsal', 'nama'),
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
            ->defaultSort('created_at', 'desc');
    }
}
