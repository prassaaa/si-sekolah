<?php

namespace App\Filament\Resources\MutasiBanks\Tables;

use App\Filament\Resources\MutasiBanks\MutasiBankResource;
use App\Models\JurnalUmum;
use App\Models\MutasiBank;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MutasiBanksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['akun', 'jurnalUmum']))
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('akun.nama')
                    ->label('Akun Bank')
                    ->description(fn (MutasiBank $record): ?string => $record->akun?->kode)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40)
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('debit')
                    ->label('Debit')
                    ->money('IDR')
                    ->alignEnd(),

                TextColumn::make('kredit')
                    ->label('Kredit')
                    ->money('IDR')
                    ->alignEnd(),

                IconColumn::make('is_matched')
                    ->label('Cocok')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('akun_id')
                    ->label('Akun Bank')
                    ->options(fn (): array => MutasiBankResource::bankAkunOptions())
                    ->searchable(),

                Filter::make('belum_cocok')
                    ->label('Belum Cocok')
                    ->query(fn ($query) => $query->unmatched())
                    ->toggle(),

                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make(),

                Action::make('tandaiCocok')
                    ->label('Tandai Cocok')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->form([
                        Select::make('jurnal_umum_id')
                            ->label('Tautkan ke Jurnal (opsional)')
                            ->options(function (MutasiBank $record): array {
                                return JurnalUmum::query()
                                    ->where('akun_id', $record->akun_id)
                                    ->orderByDesc('tanggal')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (JurnalUmum $jurnal): array => [
                                        $jurnal->id => $jurnal->nomor_bukti.' — '.$jurnal->keterangan,
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->nullable(),
                    ])
                    ->action(function (MutasiBank $record, array $data): void {
                        $record->update([
                            'is_matched' => true,
                            'jurnal_umum_id' => $data['jurnal_umum_id'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Mutasi ditandai cocok')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (MutasiBank $record): bool => ! $record->is_matched),

                Action::make('batalCocok')
                    ->label('Batal Cocok')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (MutasiBank $record): void {
                        $record->update([
                            'is_matched' => false,
                            'jurnal_umum_id' => null,
                        ]);

                        Notification::make()
                            ->title('Pencocokan dibatalkan')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (MutasiBank $record): bool => $record->is_matched),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal', 'desc');
    }
}
