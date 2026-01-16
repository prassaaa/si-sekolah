<?php

namespace App\Filament\Resources\Informasis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class InformasisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('gambar')
                    ->label('Gambar')
                    ->circular()
                    ->defaultImageUrl(fn () => asset('images/default-news.png')),
                TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(50),
                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pengumuman' => 'primary',
                        'Berita' => 'info',
                        'Kegiatan' => 'success',
                        'Prestasi' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('prioritas')
                    ->label('Prioritas')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Urgent' => 'danger',
                        'Tinggi' => 'warning',
                        'Normal' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('is_published')
                    ->label('Publish')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('is_pinned')
                    ->label('Pin')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('tanggal_publish')
                    ->label('Tgl Publish')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('views_count')
                    ->label('Views')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('createdBy.name')
                    ->label('Penulis')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options([
                        'Pengumuman' => 'Pengumuman',
                        'Berita' => 'Berita',
                        'Kegiatan' => 'Kegiatan',
                        'Prestasi' => 'Prestasi',
                        'Lainnya' => 'Lainnya',
                    ]),
                SelectFilter::make('prioritas')
                    ->label('Prioritas')
                    ->options([
                        'Rendah' => 'Rendah',
                        'Normal' => 'Normal',
                        'Tinggi' => 'Tinggi',
                        'Urgent' => 'Urgent',
                    ]),
                TernaryFilter::make('is_published')
                    ->label('Status Publikasi'),
                TernaryFilter::make('is_pinned')
                    ->label('Disematkan'),
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
