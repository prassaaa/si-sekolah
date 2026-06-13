<?php

namespace App\Filament\Resources\Anggarans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AnggaransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tahunAjaran.nama')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('akun.kode')
                    ->label('Kode Akun')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('akun.nama')
                    ->label('Nama Akun')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('akun.tipe')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pendapatan' => 'primary',
                        'beban' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('nominal_anggaran')
                    ->label('Nominal Anggaran')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->relationship('tahunAjaran', 'nama'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                ]),
            ])
            ->defaultSort('tahun_ajaran_id', 'desc');
    }
}
