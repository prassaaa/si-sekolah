<?php

namespace App\Filament\Resources\Absensis\Tables;

use App\Models\Absensi;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AbsensisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('siswa.nama')->label('Siswa')->searchable()->sortable(),
                TextColumn::make('jadwalPelajaran.kelas.nama')->label('Kelas')->badge()->color('success')->sortable(),
                TextColumn::make('jadwalPelajaran.mataPelajaran.nama')->label('Mata Pelajaran')->searchable()->sortable(),
                TextColumn::make('jadwalPelajaran.hari')->label('Hari')->badge()->color('info'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state): string => Absensi::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => Absensi::statusColors()[$state] ?? 'gray')
                    ->sortable(),
                TextColumn::make('keterangan')->label('Keterangan')->limit(30)->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Status')
                    ->options(Absensi::statusOptions()),
                SelectFilter::make('jadwal_pelajaran_id')->label('Jadwal Pelajaran')
                    ->relationship('jadwalPelajaran', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->jadwal_lengkap)
                    ->searchable()
                    ->preload(),
            ])
            ->actions([ViewAction::make(), EditAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
