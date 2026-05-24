<?php

namespace App\Filament\Resources\RfidDevices\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RfidDevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'gerbang_masuk' => 'success',
                        'gerbang_pulang' => 'info',
                        'serbaguna' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'gerbang_masuk' => 'Gerbang Masuk',
                        'gerbang_pulang' => 'Gerbang Pulang',
                        'serbaguna' => 'Serbaguna',
                        default => $state,
                    }),

                TextColumn::make('lokasi')
                    ->label('Lokasi')
                    ->placeholder('-')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('terakhir_aktif')
                    ->label('Terakhir Aktif')
                    ->dateTime('d M Y H:i')
                    ->since()
                    ->placeholder('Belum pernah'),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->options([
                        'gerbang_masuk' => 'Gerbang Masuk',
                        'gerbang_pulang' => 'Gerbang Pulang',
                        'serbaguna' => 'Serbaguna',
                    ]),
                TernaryFilter::make('is_active')->label('Status Aktif'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('regenerateToken')
                    ->label('Regenerate Token')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Regenerate API Token?')
                    ->modalDescription('Token lama akan dinonaktifkan. Firmware device harus diupdate dengan token baru.')
                    ->action(function ($record, $livewire): void {
                        $plain = $record->generateToken();

                        Notification::make()
                            ->title('Token baru')
                            ->body('Plain token: '.$plain.' — SIMPAN sekarang, tidak akan ditampilkan lagi.')
                            ->success()
                            ->persistent()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nama');
    }
}
