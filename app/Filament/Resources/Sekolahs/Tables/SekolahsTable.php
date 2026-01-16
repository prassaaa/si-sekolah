<?php

namespace App\Filament\Resources\Sekolahs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SekolahsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('npsn')
                    ->label('NPSN')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('nama')
                    ->label('Nama Sekolah')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('jenjang')
                    ->label('Jenjang')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Negeri' => 'success',
                        'Swasta' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('kepala_sekolah')
                    ->label('Kepala Sekolah')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('akreditasi')
                    ->label('Akreditasi')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'info',
                        'C' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jenjang')
                    ->label('Jenjang')
                    ->options([
                        'TK' => 'TK',
                        'RA' => 'RA',
                        'SD' => 'SD',
                        'MI' => 'MI',
                        'SMP' => 'SMP',
                        'MTs' => 'MTs',
                        'SMA' => 'SMA',
                        'MA' => 'MA',
                        'SMK' => 'SMK',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Negeri' => 'Negeri',
                        'Swasta' => 'Swasta',
                    ]),
                SelectFilter::make('akreditasi')
                    ->label('Akreditasi')
                    ->options([
                        'A' => 'A (Unggul)',
                        'B' => 'B (Baik)',
                        'C' => 'C (Cukup)',
                        'TT' => 'Tidak Terakreditasi',
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
