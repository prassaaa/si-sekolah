<?php

namespace App\Filament\Resources\Ruangans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RuangansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('nama')
                    ->label('Nama Ruangan')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'kelas' => 'Kelas',
                        'lab' => 'Laboratorium',
                        'kantor' => 'Kantor',
                        'gudang' => 'Gudang',
                        'perpustakaan' => 'Perpustakaan',
                        'aula' => 'Aula',
                        'lainnya' => 'Lainnya',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'kelas' => 'primary',
                        'lab' => 'info',
                        'kantor' => 'gray',
                        'gudang' => 'warning',
                        'perpustakaan' => 'success',
                        'aula' => 'info',
                        'lainnya' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('gedung')
                    ->label('Gedung')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lantai')
                    ->label('Lantai')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('kapasitas')
                    ->label('Kapasitas')
                    ->suffix(' orang')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('penanggungJawab.nama')
                    ->label('Penanggung Jawab')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->sortable(),
            ])
            ->defaultSort('nama', 'asc')
            ->filters([
                SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options([
                        'kelas' => 'Kelas',
                        'lab' => 'Laboratorium',
                        'kantor' => 'Kantor',
                        'gudang' => 'Gudang',
                        'perpustakaan' => 'Perpustakaan',
                        'aula' => 'Aula',
                        'lainnya' => 'Lainnya',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
