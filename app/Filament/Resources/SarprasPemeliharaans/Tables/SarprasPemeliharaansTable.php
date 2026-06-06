<?php

namespace App\Filament\Resources\SarprasPemeliharaans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SarprasPemeliharaansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('barang.nama')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'rutin' => 'Rutin',
                        'perbaikan' => 'Perbaikan',
                        'kalibrasi' => 'Kalibrasi',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'rutin' => 'info',
                        'perbaikan' => 'warning',
                        'kalibrasi' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'dijadwalkan' => 'gray',
                        'proses' => 'warning',
                        'selesai' => 'success',
                        'batal' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'dijadwalkan' => 'Dijadwalkan',
                        'proses' => 'Proses',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                        default => $state,
                    }),

                TextColumn::make('biaya')
                    ->label('Biaya')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'dijadwalkan' => 'Dijadwalkan',
                        'proses' => 'Proses',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                    ])
                    ->multiple(),

                SelectFilter::make('jenis')
                    ->options([
                        'rutin' => 'Rutin',
                        'perbaikan' => 'Perbaikan',
                        'kalibrasi' => 'Kalibrasi',
                    ])
                    ->multiple(),

                Filter::make('tanggal_range')
                    ->form([
                        DatePicker::make('dari')->label('Dari'),
                        DatePicker::make('sampai')->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['dari'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
                        ->when($data['sampai'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '<=', $v))),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal', 'desc');
    }
}
