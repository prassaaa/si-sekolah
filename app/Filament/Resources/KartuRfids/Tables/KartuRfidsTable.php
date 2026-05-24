<?php

namespace App\Filament\Resources\KartuRfids\Tables;

use App\Models\Pegawai;
use App\Models\Siswa;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class KartuRfidsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uid')
                    ->label('UID')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                TextColumn::make('owner_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === Pegawai::class ? 'Pegawai' : 'Siswa')
                    ->color(fn (string $state) => $state === Pegawai::class ? 'info' : 'primary'),

                TextColumn::make('owner.nama')
                    ->label('Nama Pemilik')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'aktif' => 'success',
                        'nonaktif' => 'gray',
                        'hilang' => 'danger',
                        'rusak' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                        'hilang' => 'Hilang',
                        'rusak' => 'Rusak',
                        default => $state,
                    }),

                TextColumn::make('diaktifkan_pada')
                    ->label('Diaktifkan')
                    ->dateTime('d M Y H:i')
                    ->toggleable(),

                TextColumn::make('dinonaktifkan_pada')
                    ->label('Dinonaktifkan')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                        'hilang' => 'Hilang',
                        'rusak' => 'Rusak',
                    ])
                    ->multiple(),

                SelectFilter::make('owner_type')
                    ->label('Tipe Pemilik')
                    ->options([
                        Siswa::class => 'Siswa',
                        Pegawai::class => 'Pegawai',
                    ]),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('tandaiHilang')
                    ->label('Tandai Hilang')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'aktif')
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Kartu Hilang?')
                    ->modalDescription('Kartu akan dinonaktifkan dan tidak bisa lagi digunakan untuk tap.')
                    ->schema([
                        Textarea::make('alasan')
                            ->label('Alasan')
                            ->placeholder('mis. Kartu hilang di sekolah, tertinggal di rumah, dst')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->tandaiHilang($data['alasan'] ?? null);
                        Notification::make()
                            ->title('Kartu ditandai hilang')
                            ->success()
                            ->send();
                    }),

                Action::make('nonaktifkan')
                    ->label('Nonaktifkan')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->visible(fn ($record) => $record->status === 'aktif')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        $record->nonaktifkan('Dinonaktifkan via panel admin');
                        Notification::make()
                            ->title('Kartu dinonaktifkan')
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
            ->defaultSort('diaktifkan_pada', 'desc');
    }
}
