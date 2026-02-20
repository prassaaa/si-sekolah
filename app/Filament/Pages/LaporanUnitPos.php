<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanUnitPosStats;
use App\Models\Pembayaran;
use App\Models\UnitPos;
use Filament\Forms\Components\DatePicker;
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

class LaporanUnitPos extends Page implements HasSchemas, HasTable
{
    use InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Laporan Unit POS';

    protected static ?string $slug = 'laporan/unit-pos';

    public ?string $activeTab = null;

    public ?Model $parentRecord = null;

    public array $summary = [];

    public ?string $unitPosNama = null;

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Transaksi Unit POS';
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
                Pembayaran::query()
                    ->with(['tagihanSiswa.siswa', 'tagihanSiswa.jenisPembayaran'])
                    ->where('status', 'berhasil')
                    ->orderBy('tanggal_bayar', 'desc')
            )
            ->columns([
                TextColumn::make('tanggal_bayar')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('nomor_transaksi')
                    ->label('No. Transaksi')
                    ->searchable(),
                TextColumn::make('tagihanSiswa.siswa.nama_lengkap')
                    ->label('Siswa')
                    ->searchable(),
                TextColumn::make('tagihanSiswa.jenisPembayaran.nama')
                    ->label('Jenis'),
                TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('jumlah_bayar')
                    ->label('Nominal')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('unit_pos_id')
                    ->label('Unit POS')
                    ->options(UnitPos::query()->where('is_active', true)->pluck('nama', 'id')),
                Filter::make('tanggal')
                    ->form([
                        DatePicker::make('tanggal_mulai')
                            ->label('Dari Tanggal')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('tanggal_selesai')
                            ->label('Sampai Tanggal')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['tanggal_mulai'], fn (Builder $q, $date) => $q->where('tanggal_bayar', '>=', $date))
                            ->when($data['tanggal_selesai'], fn (Builder $q, $date) => $q->where('tanggal_bayar', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['tanggal_mulai'] ?? null) {
                            $indicators[] = 'Dari: '.\Carbon\Carbon::parse($data['tanggal_mulai'])->translatedFormat('d M Y');
                        }

                        if ($data['tanggal_selesai'] ?? null) {
                            $indicators[] = 'Sampai: '.\Carbon\Carbon::parse($data['tanggal_selesai'])->translatedFormat('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih filter untuk melihat transaksi unit POS.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanUnitPosStats::make([
                'summary' => $this->summary,
                'unitPosNama' => $this->unitPosNama,
            ]),
        ];
    }
}
