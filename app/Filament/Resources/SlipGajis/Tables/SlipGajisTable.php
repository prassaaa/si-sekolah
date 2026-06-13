<?php

namespace App\Filament\Resources\SlipGajis\Tables;

use App\Models\SlipGaji;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SlipGajisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pegawai.nama')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('periode')
                    ->label('Periode')
                    ->getStateUsing(fn ($record) => $record->periode),
                TextColumn::make('gaji_pokok')
                    ->label('Gaji Pokok')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('total_tunjangan')
                    ->label('Tunjangan')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('total_potongan')
                    ->label('Potongan')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('gaji_bersih')
                    ->label('Gaji Bersih')
                    ->money('IDR')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'approved' => 'warning',
                        'paid' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('tanggal_bayar')
                    ->label('Tgl Bayar')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'approved' => 'Approved',
                        'paid' => 'Paid',
                    ]),
                SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                        4 => 'April', 5 => 'Mei', 6 => 'Juni',
                        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                self::approveAction(),
                self::bayarAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Aksi "Setujui": transisi draft -> approved + akrual beban gaji.
     * Dipakai di tabel maupun header halaman Edit (lihat EditSlipGaji).
     */
    public static function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Setujui')
            ->icon('heroicon-o-check-circle')
            ->color('warning')
            ->authorize(fn (): bool => auth()->user()?->can('Update:SlipGaji') ?? false)
            ->visible(fn (SlipGaji $record): bool => $record->isDraft())
            ->requiresConfirmation()
            ->modalHeading('Setujui Slip Gaji?')
            ->modalDescription('Slip akan dikunci dan beban gaji diakrualkan (D Beban Gaji / K Hutang Gaji). Tindakan ini idempoten.')
            ->action(function (SlipGaji $record): void {
                $record->approve();

                Notification::make()
                    ->title('Slip gaji disetujui dan beban gaji diakrualkan')
                    ->success()
                    ->send();
            });
    }

    /**
     * Aksi "Bayar": transisi approved -> paid + buat KasKeluar (D Hutang Gaji / K Kas).
     * Dipakai di tabel maupun header halaman Edit (lihat EditSlipGaji).
     */
    public static function bayarAction(): Action
    {
        return Action::make('bayar')
            ->label('Bayar')
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->authorize(fn (): bool => auth()->user()?->can('Update:SlipGaji') ?? false)
            ->visible(fn (SlipGaji $record): bool => $record->isApproved())
            ->requiresConfirmation()
            ->modalHeading('Bayar Slip Gaji?')
            ->modalDescription('Kas keluar pembayaran gaji akan dibuat dan dijurnal (D Hutang Gaji / K Kas). Tindakan ini idempoten.')
            ->action(function (SlipGaji $record): void {
                $record->bayar();

                Notification::make()
                    ->title('Slip gaji dibayar dan kas keluar tercatat')
                    ->success()
                    ->send();
            });
    }
}
