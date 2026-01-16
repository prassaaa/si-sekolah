<?php

namespace App\Filament\Resources\JadwalPelajarans\Tables;

use App\Models\JadwalPelajaran;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class JadwalPelajaransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('semester.nama')
                    ->label('Semester')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('kelas.nama')
                    ->label('Kelas')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('hari')
                    ->label('Hari')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('jamPelajaran.nama')
                    ->label('Jam')
                    ->sortable(),
                TextColumn::make('waktu')
                    ->label('Waktu')
                    ->sortable(),
                TextColumn::make('mataPelajaran.nama')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('guru.nama')
                    ->label('Guru')
                    ->searchable()
                    ->placeholder('Belum ditentukan'),
                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->sortable(),
            ])
            ->defaultSort('hari', 'asc')
            ->groups([
                Group::make('kelas.nama')
                    ->label('Kelas')
                    ->collapsible(),
                Group::make('hari')
                    ->label('Hari')
                    ->collapsible(),
            ])
            ->defaultGroup('kelas.nama')
            ->filters([
                SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->relationship('semester', 'nama'),
                SelectFilter::make('kelas_id')
                    ->label('Kelas')
                    ->relationship('kelas', 'nama')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('hari')
                    ->label('Hari')
                    ->options(JadwalPelajaran::hariOptions()),
                SelectFilter::make('mata_pelajaran_id')
                    ->label('Mata Pelajaran')
                    ->relationship('mataPelajaran', 'nama')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('guru_id')
                    ->label('Guru')
                    ->relationship('guru', 'nama')
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
