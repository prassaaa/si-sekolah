<?php

namespace App\Filament\Resources\Kelases\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class KelasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tahunAjaran.kode')
                    ->label('Tahun Ajaran')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('nama')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => "Kelas $state")
                    ->badge()
                    ->color('info'),
                TextColumn::make('jurusan')
                    ->label('Jurusan')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('waliKelas.nama')
                    ->label('Wali Kelas')
                    ->placeholder('Belum ditentukan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('kapasitas')
                    ->label('Kapasitas')
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('siswas_count')
                    ->label('Siswa')
                    ->counts('siswas')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('success'),
                TextColumn::make('ruangan')
                    ->label('Ruangan')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->sortable(),
            ])
            ->defaultSort('urutan', 'asc')
            ->filters([
                SelectFilter::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->relationship('tahunAjaran', 'nama'),
                SelectFilter::make('tingkat')
                    ->label('Tingkat')
                    ->options([
                        7 => 'Kelas 7',
                        8 => 'Kelas 8',
                        9 => 'Kelas 9',
                        10 => 'Kelas 10',
                        11 => 'Kelas 11',
                        12 => 'Kelas 12',
                    ]),
                SelectFilter::make('wali_kelas_id')
                    ->label('Wali Kelas')
                    ->relationship('waliKelas', 'nama')
                    ->searchable()
                    ->preload(),
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
