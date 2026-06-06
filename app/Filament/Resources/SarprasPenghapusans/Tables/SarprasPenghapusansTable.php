<?php

namespace App\Filament\Resources\SarprasPenghapusans\Tables;

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

class SarprasPenghapusansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('barang'))
            ->columns([
                TextColumn::make('nomor')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('barang.nama')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('alasan')
                    ->label('Alasan')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'rusak_berat' => 'Rusak Berat',
                        'hilang' => 'Hilang',
                        'usang' => 'Usang',
                        'lainnya' => 'Lainnya',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'rusak_berat' => 'danger',
                        'hilang' => 'warning',
                        'usang' => 'gray',
                        'lainnya' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('metode')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'dibuang' => 'Dibuang',
                        'dijual' => 'Dijual',
                        'disumbangkan' => 'Disumbangkan',
                        default => $state,
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'diajukan' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'diajukan' => 'Diajukan',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        default => $state,
                    }),

                TextColumn::make('nilai_sisa')
                    ->label('Nilai Sisa')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'diajukan' => 'Diajukan',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ])
                    ->multiple(),

                SelectFilter::make('alasan')
                    ->options([
                        'rusak_berat' => 'Rusak Berat',
                        'hilang' => 'Hilang',
                        'usang' => 'Usang',
                        'lainnya' => 'Lainnya',
                    ])
                    ->multiple(),

                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('dari')->label('Dari Tanggal')->native(false),
                        DatePicker::make('sampai')->label('Sampai Tanggal')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['dari'], fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
                            ->when($data['sampai'], fn ($q, $v) => $q->whereDate('tanggal', '<=', $v));
                    }),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('setujui')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'diajukan')
                    ->requiresConfirmation()
                    ->modalHeading('Setujui Penghapusan?')
                    ->modalDescription('Barang akan ditandai sebagai dihapus dan dinonaktifkan.')
                    ->action(function ($record): void {
                        if (! $record->disetujui_oleh) {
                            $record->update(['disetujui_oleh' => auth()->id()]);
                        }
                        $record->setujui();
                        Notification::make()
                            ->title('Penghapusan disetujui')
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
