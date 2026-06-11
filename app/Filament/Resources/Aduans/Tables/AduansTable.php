<?php

namespace App\Filament\Resources\Aduans\Tables;

use App\Models\Aduan;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class AduansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['siswa', 'penangan']))
            ->columns([
                TextColumn::make('judul')
                    ->label('Judul')
                    ->searchable()
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('pelapor')
                    ->label('Pelapor')
                    ->searchable()
                    ->description(fn (Aduan $record) => match ($record->hubungan_pelapor) {
                        'siswa' => 'Siswa',
                        'ayah' => 'Ayah',
                        'ibu' => 'Ibu',
                        'wali' => 'Wali',
                        default => 'Lainnya',
                    }),

                TextColumn::make('kategori')
                    ->label('Kategori')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'akademik' => 'Akademik',
                        'fasilitas' => 'Fasilitas',
                        'perlakuan' => 'Perlakuan',
                        'keuangan' => 'Keuangan',
                        default => 'Lainnya',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'akademik' => 'info',
                        'fasilitas' => 'warning',
                        'perlakuan' => 'danger',
                        'keuangan' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'baru' => 'Baru',
                        'diproses' => 'Diproses',
                        'selesai' => 'Selesai',
                        'ditolak' => 'Ditolak',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'baru' => 'danger',
                        'diproses' => 'warning',
                        'selesai' => 'success',
                        'ditolak' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('tanggal_aduan')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('penangan.nama')
                    ->label('Penanganan')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options([
                        'akademik' => 'Akademik',
                        'fasilitas' => 'Fasilitas',
                        'perlakuan' => 'Perlakuan',
                        'keuangan' => 'Keuangan',
                        'lainnya' => 'Lainnya',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'baru' => 'Baru',
                        'diproses' => 'Diproses',
                        'selesai' => 'Selesai',
                        'ditolak' => 'Ditolak',
                    ]),

                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('tanggapi')
                    ->label('Tanggapi')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->form([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'diproses' => 'Diproses',
                                'selesai' => 'Selesai',
                                'ditolak' => 'Ditolak',
                            ])
                            ->required(),

                        Textarea::make('tanggapan')
                            ->label('Tanggapan')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (Aduan $record, array $data) {
                        $pegawaiId = auth()->user()?->pegawai?->id;

                        if ($pegawaiId) {
                            $record->tanggapi($data['tanggapan'], $pegawaiId, $data['status']);
                        } else {
                            $record->update([
                                'tanggapan' => $data['tanggapan'],
                                'status' => $data['status'],
                                'tanggal_tanggapan' => now(),
                            ]);
                        }

                        Notification::make()
                            ->title('Tanggapan berhasil disimpan')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Aduan $record) => in_array($record->status, ['baru', 'diproses'])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal_aduan', 'desc');
    }
}
