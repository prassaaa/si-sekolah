<?php

namespace App\Filament\Resources\Konselings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class KonselingsTable
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

                TextColumn::make('konselor.nama')
                    ->label('Konselor')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'individu' => 'Individu',
                        'kelompok' => 'Kelompok',
                        'keluarga' => 'Keluarga',
                        default => $state,
                    }),

                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'akademik' => 'Akademik',
                        'pribadi' => 'Pribadi',
                        'sosial' => 'Sosial',
                        'karir' => 'Karir',
                        'lainnya' => 'Lainnya',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'akademik' => 'info',
                        'pribadi' => 'warning',
                        'sosial' => 'success',
                        'karir' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'dijadwalkan' => 'Dijadwalkan',
                        'berlangsung' => 'Berlangsung',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'dijadwalkan' => 'info',
                        'berlangsung' => 'warning',
                        'selesai' => 'success',
                        'batal' => 'danger',
                        default => 'gray',
                    }),

                IconColumn::make('perlu_tindak_lanjut')
                    ->label('Tindak Lanjut')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('permasalahan')
                    ->label('Permasalahan')
                    ->limit(40)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->options([
                        'individu' => 'Individu',
                        'kelompok' => 'Kelompok',
                        'keluarga' => 'Keluarga',
                    ]),
                SelectFilter::make('kategori')
                    ->options([
                        'akademik' => 'Akademik',
                        'pribadi' => 'Pribadi',
                        'sosial' => 'Sosial',
                        'karir' => 'Karir',
                        'lainnya' => 'Lainnya',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'dijadwalkan' => 'Dijadwalkan',
                        'berlangsung' => 'Berlangsung',
                        'selesai' => 'Selesai',
                        'batal' => 'Dibatalkan',
                    ]),
                TernaryFilter::make('perlu_tindak_lanjut')
                    ->label('Perlu Tindak Lanjut'),
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
