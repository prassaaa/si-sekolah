<?php

namespace App\Filament\Resources\JabatanPegawais\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class JabatanPegawaisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('nama')
                    ->label('Nama Jabatan')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Struktural' => 'success',
                        'Fungsional' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('golongan')
                    ->label('Golongan')
                    ->badge()
                    ->color('warning')
                    ->sortable()
                    ->default('-'),
                TextColumn::make('gaji_pokok')
                    ->label('Gaji Pokok')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tunjangan')
                    ->label('Tunjangan')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pegawais_count')
                    ->label('Pegawai')
                    ->counts('pegawais')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('urutan')
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Jenis Jabatan')
                    ->options([
                        'Struktural' => 'Struktural',
                        'Fungsional' => 'Fungsional',
                        'Non-Fungsional' => 'Non-Fungsional',
                    ]),
                SelectFilter::make('golongan')
                    ->label('Golongan')
                    ->options([
                        'I' => 'Golongan I',
                        'II' => 'Golongan II',
                        'III' => 'Golongan III',
                        'IV' => 'Golongan IV',
                        'Non-PNS' => 'Non-PNS',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
