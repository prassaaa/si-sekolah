<?php

namespace App\Filament\Resources\PeriodeAkuntansis\Tables;

use App\Models\PeriodeAkuntansi;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PeriodeAkuntansisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('penutup'))
            ->columns([
                TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable(),

                TextColumn::make('nama_bulan')
                    ->label('Bulan')
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('bulan', $direction)),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'closed' ? 'Tertutup' : 'Terbuka')
                    ->color(fn (string $state): string => $state === 'closed' ? 'danger' : 'success'),

                TextColumn::make('penutup.name')
                    ->label('Ditutup Oleh')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('closed_at')
                    ->label('Waktu Tutup')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'open' => 'Terbuka',
                        'closed' => 'Tertutup',
                    ]),

                SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(fn (): array => PeriodeAkuntansi::query()
                        ->distinct()
                        ->orderByDesc('tahun')
                        ->pluck('tahun', 'tahun')
                        ->map(fn ($tahun): string => (string) $tahun)
                        ->all()),
            ])
            ->actions([
                Action::make('tutup')
                    ->label('Tutup Periode')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tutup Periode')
                    ->modalDescription(fn (PeriodeAkuntansi $record): string => "Tutup periode {$record->label_periode}? Setelah ditutup, transaksi pada periode ini tidak dapat dibuat, diubah, atau dihapus.")
                    ->schema([
                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->action(function (PeriodeAkuntansi $record, array $data): void {
                        $record->update([
                            'status' => 'closed',
                            'closed_by' => auth()->id(),
                            'closed_at' => now(),
                            'keterangan' => $data['keterangan'] ?? $record->keterangan,
                        ]);

                        Notification::make()
                            ->title("Periode {$record->label_periode} telah ditutup")
                            ->success()
                            ->send();
                    })
                    ->authorize('tutup')
                    ->visible(fn (PeriodeAkuntansi $record): bool => ! $record->isTertutup()),

                Action::make('buka')
                    ->label('Buka Kembali')
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Buka Kembali Periode')
                    ->modalDescription(fn (PeriodeAkuntansi $record): string => "Buka kembali periode {$record->label_periode}? Transaksi pada periode ini akan dapat diubah lagi.")
                    ->action(function (PeriodeAkuntansi $record): void {
                        $record->update([
                            'status' => 'open',
                            'closed_by' => null,
                            'closed_at' => null,
                        ]);

                        Notification::make()
                            ->title("Periode {$record->label_periode} telah dibuka kembali")
                            ->success()
                            ->send();
                    })
                    ->authorize('reopen')
                    ->visible(fn (PeriodeAkuntansi $record): bool => $record->isTertutup()),
            ])
            ->defaultSort('tahun', 'desc');
    }
}
