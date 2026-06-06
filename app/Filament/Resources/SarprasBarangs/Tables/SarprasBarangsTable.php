<?php

namespace App\Filament\Resources\SarprasBarangs\Tables;

use App\Models\SarprasBarang;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SarprasBarangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(
                SarprasBarang::query()
                    ->with(['kategori', 'ruangan'])
            )
            ->columns([
                TextColumn::make('kode_inventaris')
                    ->label('Kode Inventaris')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('nama')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('kategori.nama')
                    ->label('Kategori')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('ruangan.nama')
                    ->label('Ruangan')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('kondisi')
                    ->label('Kondisi')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->kondisi_info['label'])
                    ->color(fn ($record) => $record->kondisi_info['color']),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->status_info['label'])
                    ->color(fn ($record) => $record->status_info['color']),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->sortable()
                    ->suffix(fn ($record) => ' '.$record->satuan),
                TextColumn::make('harga_perolehan')
                    ->label('Harga Perolehan')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->sortable(),
            ])
            ->defaultSort('kode_inventaris', 'asc')
            ->filters([
                SelectFilter::make('sarpras_kategori_id')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('ruangan_id')
                    ->label('Ruangan')
                    ->relationship('ruangan', 'nama')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('kondisi')
                    ->label('Kondisi')
                    ->options([
                        'baik' => 'Baik',
                        'rusak_ringan' => 'Rusak Ringan',
                        'rusak_berat' => 'Rusak Berat',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'tersedia' => 'Tersedia',
                        'dipinjam' => 'Dipinjam',
                        'perbaikan' => 'Perbaikan',
                        'dihapus' => 'Dihapus',
                    ]),
                SelectFilter::make('tipe')
                    ->label('Tipe')
                    ->options([
                        'aset' => 'Aset',
                        'bahan' => 'Bahan',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak Aktif'),
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
