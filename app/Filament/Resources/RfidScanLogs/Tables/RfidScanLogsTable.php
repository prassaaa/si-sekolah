<?php

namespace App\Filament\Resources\RfidScanLogs\Tables;

use App\Models\Pegawai;
use App\Models\Siswa;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RfidScanLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scanned_at')
                    ->label('Waktu Scan')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),

                TextColumn::make('uid')
                    ->label('UID')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('owner.nama')
                    ->label('Pemilik')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('owner_type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        Siswa::class => 'Siswa',
                        Pegawai::class => 'Pegawai',
                        default => '-',
                    })
                    ->toggleable(),

                TextColumn::make('device.nama')
                    ->label('Device')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('jenis')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'masuk' => 'success',
                        'pulang' => 'info',
                        'duplikat' => 'warning',
                        'ditolak' => 'danger',
                        'tidak_dikenal' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'masuk' => 'Masuk',
                        'pulang' => 'Pulang',
                        'duplikat' => 'Duplikat',
                        'ditolak' => 'Ditolak',
                        'tidak_dikenal' => 'Tidak Dikenal',
                        default => $state,
                    }),

                TextColumn::make('pesan')
                    ->label('Pesan')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),
            ])
            ->filters([
                SelectFilter::make('jenis')
                    ->options([
                        'masuk' => 'Masuk',
                        'pulang' => 'Pulang',
                        'duplikat' => 'Duplikat',
                        'ditolak' => 'Ditolak',
                        'tidak_dikenal' => 'Tidak Dikenal',
                    ])
                    ->multiple(),

                SelectFilter::make('rfid_device_id')
                    ->label('Device')
                    ->relationship('device', 'nama'),

                Filter::make('hanya_gagal')
                    ->label('Hanya Gagal')
                    ->query(fn (Builder $query) => $query->whereIn('jenis', ['ditolak', 'tidak_dikenal'])),

                Filter::make('scanned_range')
                    ->form([
                        DatePicker::make('dari')->label('Dari'),
                        DatePicker::make('sampai')->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['dari'] ?? null, fn ($q, $v) => $q->whereDate('scanned_at', '>=', $v))
                        ->when($data['sampai'] ?? null, fn ($q, $v) => $q->whereDate('scanned_at', '<=', $v))),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('scanned_at', 'desc')
            ->defaultPaginationPageOption(50);
    }
}
