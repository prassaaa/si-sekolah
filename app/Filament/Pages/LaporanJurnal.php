<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Laporan\LaporanJurnalStats;
use App\Models\JurnalUmum;
use App\Services\Accounting\LaporanPdfService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanJurnal extends Page implements HasSchemas, HasTable
{
    use ExposesTableToWidgets, InteractsWithSchemas, InteractsWithTable;
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static \UnitEnum|string|null $navigationGroup = 'Akuntansi';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'Laporan Jurnal';

    protected static ?string $slug = 'laporan/jurnal';

    public ?string $activeTab = null;

    public ?Model $parentRecord = null;

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Jurnal Umum';
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
                JurnalUmum::query()
                    ->with('akun')
                    ->orderBy('tanggal')
                    ->orderBy('nomor_bukti')
            )
            ->columns([
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('nomor_bukti')
                    ->label('No. Bukti')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('akun.nama')
                    ->label('Akun')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('debit')
                    ->label('Debit')
                    ->money('IDR')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
                TextColumn::make('kredit')
                    ->label('Kredit')
                    ->money('IDR')
                    ->alignEnd()
                    ->summarize(Sum::make()->money('IDR')->label('Total')),
            ])
            ->filters([
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
                            ->when($data['tanggal_mulai'], fn (Builder $q, $date) => $q->where('tanggal', '>=', $date))
                            ->when($data['tanggal_selesai'], fn (Builder $q, $date) => $q->where('tanggal', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['tanggal_mulai'] ?? null) {
                            $indicators[] = 'Dari: '.Carbon::parse($data['tanggal_mulai'])->translatedFormat('d M Y');
                        }

                        if ($data['tanggal_selesai'] ?? null) {
                            $indicators[] = 'Sampai: '.Carbon::parse($data['tanggal_selesai'])->translatedFormat('d M Y');
                        }

                        return $indicators;
                    }),
            ])
            ->deferFilters(false)
            ->defaultPaginationPageOption('all')
            ->emptyStateHeading('Tidak ada data')
            ->emptyStateDescription('Silakan pilih rentang tanggal untuk melihat jurnal umum.')
            ->emptyStateIcon('heroicon-o-inbox');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetakPdf')
                ->label('Cetak PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn (): StreamedResponse => $this->cetakPdf()),
        ];
    }

    public function cetakPdf(): StreamedResponse
    {
        $tanggalFilter = $this->getTableFilterState('tanggal') ?? [];
        $tanggalMulai = $tanggalFilter['tanggal_mulai'] ?? null;
        $tanggalSelesai = $tanggalFilter['tanggal_selesai'] ?? null;

        $entries = JurnalUmum::query()
            ->with('akun')
            ->when($tanggalMulai, fn (Builder $q, $date) => $q->where('tanggal', '>=', $date))
            ->when($tanggalSelesai, fn (Builder $q, $date) => $q->where('tanggal', '<=', $date))
            ->orderBy('tanggal')
            ->orderBy('nomor_bukti')
            ->get();

        $totalDebit = '0';
        $totalKredit = '0';

        $baris = $entries->map(function (JurnalUmum $entry) use (&$totalDebit, &$totalKredit): array {
            $totalDebit = bcadd($totalDebit, (string) $entry->debit, 2);
            $totalKredit = bcadd($totalKredit, (string) $entry->kredit, 2);

            return [
                Carbon::parse($entry->tanggal)->format('d/m/Y'),
                $entry->nomor_bukti,
                $entry->akun?->nama ?? '-',
                $entry->keterangan,
                number_format((float) $entry->debit, 0, ',', '.'),
                number_format((float) $entry->kredit, 0, ',', '.'),
            ];
        })->all();

        $ringkasan = [[
            'TOTAL',
            '',
            '',
            '',
            number_format((float) $totalDebit, 0, ',', '.'),
            number_format((float) $totalKredit, 0, ',', '.'),
        ]];

        $periode = ($tanggalMulai && $tanggalSelesai)
            ? 'Periode '.Carbon::parse($tanggalMulai)->translatedFormat('d M Y')
                .' s.d. '.Carbon::parse($tanggalSelesai)->translatedFormat('d M Y')
            : 'Semua Periode';

        $pdf = LaporanPdfService::make()
            ->judul('LAPORAN JURNAL UMUM')
            ->periode($periode)
            ->kolom([
                'Tanggal',
                'No. Bukti',
                'Akun',
                'Keterangan',
                ['Debit (Rp)', 'right'],
                ['Kredit (Rp)', 'right'],
            ])
            ->baris($baris)
            ->ringkasan($ringkasan)
            ->landscape()
            ->render();

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            LaporanPdfService::make()->namaFile('jurnal-umum-'.($tanggalSelesai ?? now()->toDateString())),
        );
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LaporanJurnalStats::class,
        ];
    }
}
