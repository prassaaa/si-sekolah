<?php

namespace App\Filament\Resources\SarprasPeminjamans\Tables;

use App\Models\SarprasPeminjaman;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SarprasPeminjamansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['barang', 'peminjam', 'petugas']))
            ->columns([
                TextColumn::make('nomor')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('barang.nama')
                    ->label('Barang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('peminjam.nama')
                    ->label('Peminjam')
                    ->searchable(),

                TextColumn::make('tanggal_pinjam')
                    ->label('Tgl Pinjam')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('tanggal_harus_kembali')
                    ->label('Harus Kembali')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (SarprasPeminjaman $record) => $record->status_info['color'])
                    ->formatStateUsing(fn (SarprasPeminjaman $record) => $record->status_info['label']),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'dipinjam' => 'Dipinjam',
                        'dikembalikan' => 'Dikembalikan',
                        'terlambat' => 'Terlambat',
                        'hilang' => 'Hilang',
                    ])
                    ->multiple(),

                Filter::make('tanggal_pinjam')
                    ->label('Rentang Tanggal Pinjam')
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
                            ->when($data['dari'], fn ($q) => $q->whereDate('tanggal_pinjam', '>=', $data['dari']))
                            ->when($data['sampai'], fn ($q) => $q->whereDate('tanggal_pinjam', '<=', $data['sampai']));
                    }),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('kembalikan')
                    ->label('Kembalikan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->visible(fn (SarprasPeminjaman $record) => in_array($record->status, ['dipinjam', 'terlambat']))
                    ->schema([
                        Select::make('kondisi_kembali')
                            ->label('Kondisi Barang Saat Kembali')
                            ->required()
                            ->options([
                                'baik' => 'Baik',
                                'rusak_ringan' => 'Rusak Ringan',
                                'rusak_berat' => 'Rusak Berat',
                            ])
                            ->default('baik'),
                    ])
                    ->action(function (SarprasPeminjaman $record, array $data): void {
                        $record->kembalikan($data['kondisi_kembali']);

                        Notification::make()
                            ->title('Barang berhasil dikembalikan')
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
            ->defaultSort('tanggal_pinjam', 'desc');
    }
}
