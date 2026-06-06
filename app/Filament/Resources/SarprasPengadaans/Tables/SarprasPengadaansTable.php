<?php

namespace App\Filament\Resources\SarprasPengadaans\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SarprasPengadaansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('items'))
            ->columns([
                TextColumn::make('nomor')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('penyedia')
                    ->label('Penyedia')
                    ->searchable()
                    ->placeholder('-')
                    ->limit(30),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'draft' => 'gray',
                        'disetujui' => 'info',
                        'diterima' => 'success',
                        'batal' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'draft' => 'Draft',
                        'disetujui' => 'Disetujui',
                        'diterima' => 'Diterima',
                        'batal' => 'Batal',
                        default => $state,
                    }),

                TextColumn::make('total_biaya')
                    ->label('Total Biaya')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Jumlah Item')
                    ->counts('items')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'disetujui' => 'Disetujui',
                        'diterima' => 'Diterima',
                        'batal' => 'Batal',
                    ])
                    ->multiple(),

                SelectFilter::make('sumber_dana')
                    ->label('Sumber Dana')
                    ->options([
                        'bos' => 'BOS',
                        'komite' => 'Komite',
                        'yayasan' => 'Yayasan',
                        'hibah' => 'Hibah',
                        'pribadi' => 'Pribadi',
                        'lainnya' => 'Lainnya',
                    ])
                    ->multiple(),

                Filter::make('tanggal')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('dari')
                            ->label('Dari')
                            ->native(false),
                        DatePicker::make('sampai')
                            ->label('Sampai')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari'], fn (Builder $q) => $q->whereDate('tanggal', '>=', $data['dari']))
                            ->when($data['sampai'], fn (Builder $q) => $q->whereDate('tanggal', '<=', $data['sampai']));
                    }),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('terima')
                    ->label('Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'disetujui')
                    ->requiresConfirmation()
                    ->modalHeading('Terima Pengadaan?')
                    ->modalDescription('Barang akan dimasukkan ke stok inventaris. Tindakan ini idempoten.')
                    ->action(function ($record): void {
                        $record->terima();
                        Notification::make()
                            ->title('Pengadaan diterima dan stok diperbarui')
                            ->success()
                            ->send();
                    }),
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
