<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanGajiStats;
use App\Models\SlipGaji;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LaporanGaji extends Page implements HasSchemas, HasTable
{
    use ExposesTableToWidgets, InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Laporan Gaji';

    protected static ?string $slug = 'laporan/gaji';

    public ?string $activeTab = null;

    public ?Model $parentRecord = null;

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Slip Gaji';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SlipGaji::query()->with('pegawai')
            )
            ->columns([
                TextColumn::make('pegawai.nip')
                    ->label('NIP')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pegawai.nama_lengkap')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('gaji_pokok')
                    ->label('Gaji Pokok')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_tunjangan')
                    ->label('Tunjangan')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('total_potongan')
                    ->label('Potongan')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('gaji_bersih')
                    ->label('Gaji Bersih')
                    ->money('IDR')
                    ->alignEnd()
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'dibayar' => 'success',
                        'disetujui' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->alignCenter(),
            ])
            ->filters([
                Filter::make('bulan')
                    ->form([
                        DatePicker::make('bulan')
                            ->label('Bulan')
                            ->displayFormat('F Y')
                            ->default(now()->startOfMonth()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['bulan']) {
                            return $query;
                        }

                        $date = \Carbon\Carbon::parse($data['bulan']);

                        return $query
                            ->where('tahun', $date->year)
                            ->where('bulan', $date->month);
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['bulan']) {
                            return null;
                        }

                        return 'Bulan: '.\Carbon\Carbon::parse($data['bulan'])->translatedFormat('F Y');
                    }),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'disetujui' => 'Disetujui',
                        'dibayar' => 'Dibayar',
                    ]),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih bulan untuk melihat data slip gaji.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanGajiStats::class,
        ];
    }
}
