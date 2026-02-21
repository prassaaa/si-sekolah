<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanTagihanSiswaStats;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\TagihanSiswa;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class LaporanTagihanSiswa extends Page implements HasSchemas, HasTable
{
    use ExposesTableToWidgets, InteractsWithSchemas, InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static \UnitEnum|string|null $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'Laporan Tagihan Siswa';

    protected static ?string $slug = 'laporan/tagihan-siswa';

    public ?string $activeTab = null;

    public ?Model $parentRecord = null;

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Tagihan Siswa';
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
        $activeSemesterId = Semester::query()->where('is_active', true)->value('id');

        return $table
            ->query(
                TagihanSiswa::query()
                    ->with(['siswa.kelas', 'jenisPembayaran'])
                    ->orderBy('tanggal_jatuh_tempo')
            )
            ->columns([
                TextColumn::make('nomor_tagihan')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('siswa.nama_lengkap')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('siswa.kelas.nama')
                    ->label('Kelas')
                    ->sortable(),
                TextColumn::make('jenisPembayaran.nama')
                    ->label('Jenis Tagihan')
                    ->sortable(),
                TextColumn::make('total_tagihan')
                    ->label('Tagihan')
                    ->money('IDR')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('total_terbayar')
                    ->label('Terbayar')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('sisa_tagihan')
                    ->label('Sisa')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('tanggal_jatuh_tempo')
                    ->label('Jatuh Tempo')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'lunas' => 'success',
                        'sebagian' => 'warning',
                        'batal' => 'gray',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'belum_bayar' => 'Belum Bayar',
                        'sebagian' => 'Sebagian',
                        'lunas' => 'Lunas',
                        'batal' => 'Batal',
                        default => ucfirst($state),
                    })
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('semester_id')
                    ->label('Semester')
                    ->options(Semester::query()->orderByDesc('tahun_ajaran_id')->pluck('nama', 'id'))
                    ->default($activeSemesterId),
                SelectFilter::make('kelas')
                    ->label('Kelas')
                    ->options(Kelas::query()->where('is_active', true)->orderBy('tingkat')->orderBy('nama')->pluck('nama', 'id'))
                    ->query(fn ($query, array $data) => $data['value'] ? $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $data['value'])) : $query),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'belum_bayar' => 'Belum Bayar',
                        'sebagian' => 'Sebagian',
                        'lunas' => 'Lunas',
                        'batal' => 'Batal',
                    ]),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih semester untuk melihat data tagihan.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanTagihanSiswaStats::class,
        ];
    }
}
