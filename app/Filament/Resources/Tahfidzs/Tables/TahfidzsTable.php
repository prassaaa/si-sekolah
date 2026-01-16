<?php

namespace App\Filament\Resources\Tahfidzs\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TahfidzsTable
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
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('surah')
                    ->label('Surah')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('hafalan')
                    ->label('Hafalan')
                    ->getStateUsing(fn ($record) => "{$record->ayat_mulai} - {$record->ayat_selesai}")
                    ->toggleable(),

                TextColumn::make('jumlah_ayat')
                    ->label('Jumlah')
                    ->suffix(' ayat')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('juz')
                    ->label('Juz')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'setoran' => 'Setoran',
                        'murojaah' => 'Murojaah',
                        'ujian' => 'Ujian',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'setoran' => 'info',
                        'murojaah' => 'warning',
                        'ujian' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'lulus' => 'Lulus',
                        'mengulang' => 'Mengulang',
                        'pending' => 'Pending',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'lulus' => 'success',
                        'mengulang' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('nilai')
                    ->label('Nilai')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('penguji.nama')
                    ->label('Penguji')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('semester.nama')
                    ->label('Semester')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options([
                        'setoran' => 'Setoran',
                        'murojaah' => 'Murojaah',
                        'ujian' => 'Ujian',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'lulus' => 'Lulus',
                        'mengulang' => 'Mengulang',
                        'pending' => 'Pending',
                    ]),

                SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->relationship('semester', 'nama'),
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
