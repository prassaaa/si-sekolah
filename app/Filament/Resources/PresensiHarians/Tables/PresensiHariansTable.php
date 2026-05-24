<?php

namespace App\Filament\Resources\PresensiHarians\Tables;

use App\Models\Kelas;
use App\Models\PresensiHarian;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PresensiHariansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('siswa.nis')
                    ->label('NIS')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('siswa.nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('siswa.kelas.nama')
                    ->label('Kelas')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('jam_masuk')
                    ->label('Jam Masuk')
                    ->time('H:i')
                    ->placeholder('-'),

                TextColumn::make('jam_pulang')
                    ->label('Jam Pulang')
                    ->time('H:i')
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'hadir' => 'success',
                        'terlambat' => 'warning',
                        'izin' => 'info',
                        'sakit' => 'gray',
                        'alpha' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => PresensiHarian::statusOptions()[$state] ?? $state),

                TextColumn::make('sumber_masuk')
                    ->label('Sumber')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('terlambat_menit')
                    ->label('Telat (mnt)')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(PresensiHarian::statusOptions())
                    ->multiple(),

                SelectFilter::make('siswa_kelas')
                    ->label('Kelas')
                    ->options(fn () => Kelas::query()->orderBy('nama')->pluck('nama', 'id'))
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $data['value']))
                        : $query),

                Filter::make('tanggal_range')
                    ->form([
                        DatePicker::make('dari')->label('Dari'),
                        DatePicker::make('sampai')->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['dari'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '>=', $v))
                        ->when($data['sampai'] ?? null, fn ($q, $v) => $q->whereDate('tanggal', '<=', $v))),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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
