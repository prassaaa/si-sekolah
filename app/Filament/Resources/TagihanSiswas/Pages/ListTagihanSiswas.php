<?php

namespace App\Filament\Resources\TagihanSiswas\Pages;

use App\Filament\Resources\TagihanSiswas\TagihanSiswaResource;
use App\Models\JenisPembayaran;
use App\Models\Kelas;
use App\Models\PembayaranPaket;
use App\Models\Semester;
use App\Models\Siswa;
use App\Services\Accounting\GeneratorTagihanService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListTagihanSiswas extends ListRecords
{
    protected static string $resource = TagihanSiswaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            static::generateMassalAction(),
            static::terapkanPaketAction(),
            Actions\CreateAction::make(),
        ];
    }

    /**
     * Aksi header "Generate Tagihan Massal": membuat tagihan satu jenis
     * pembayaran untuk seluruh siswa aktif pada satu periode (bulan + tahun).
     * Idempoten — siswa yang sudah memiliki tagihan periode tersebut dilewati.
     */
    public static function generateMassalAction(): Action
    {
        return Action::make('generateMassal')
            ->label('Generate Tagihan Massal')
            ->icon('heroicon-o-document-duplicate')
            ->color('primary')
            ->authorize(fn (): bool => auth()->user()?->can('Create:TagihanSiswa') ?? false)
            ->modalHeading('Generate Tagihan Massal')
            ->modalDescription('Membuat tagihan untuk seluruh siswa aktif pada periode terpilih. Aman dijalankan berulang: tagihan yang sudah ada akan dilewati.')
            ->modalSubmitActionLabel('Generate')
            ->schema([
                Select::make('jenis_pembayaran_id')
                    ->label('Jenis Pembayaran')
                    ->options(fn (): array => static::jenisPembayaranOptions())
                    ->searchable()
                    ->required(),

                Select::make('semester_id')
                    ->label('Semester')
                    ->options(fn (): array => static::semesterOptions())
                    ->searchable()
                    ->required(),

                Select::make('kelas_id')
                    ->label('Kelas')
                    ->placeholder('Semua kelas')
                    ->options(fn (): array => static::kelasOptions())
                    ->searchable(),

                Select::make('bulan')
                    ->label('Bulan')
                    ->options(static::bulanOptions())
                    ->default((int) now()->format('n'))
                    ->required(),

                TextInput::make('tahun')
                    ->label('Tahun')
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->default((int) now()->format('Y'))
                    ->required(),
            ])
            ->action(function (array $data): void {
                $jenis = JenisPembayaran::findOrFail($data['jenis_pembayaran_id']);
                $semester = Semester::findOrFail($data['semester_id']);

                $hasil = app(GeneratorTagihanService::class)->generateMassal(
                    $jenis,
                    $semester,
                    $data['kelas_id'] !== null ? (int) $data['kelas_id'] : null,
                    (int) $data['bulan'],
                    (int) $data['tahun'],
                );

                Notification::make()
                    ->title("{$hasil['dibuat']} tagihan dibuat, {$hasil['dilewati']} dilewati")
                    ->success()
                    ->send();
            });
    }

    /**
     * Aksi header "Terapkan Paket": membuat tagihan untuk tiap jenis pembayaran
     * dalam paket pada satu siswa + semester. Idempoten per (siswa, jenis,
     * semester).
     */
    public static function terapkanPaketAction(): Action
    {
        return Action::make('terapkanPaket')
            ->label('Terapkan Paket')
            ->icon('heroicon-o-rectangle-stack')
            ->color('gray')
            ->authorize(fn (): bool => auth()->user()?->can('Create:TagihanSiswa') ?? false)
            ->modalHeading('Terapkan Paket Pembayaran')
            ->modalDescription('Membuat tagihan untuk seluruh item paket pada satu siswa dan semester. Aman dijalankan berulang: item yang tagihannya sudah ada akan dilewati.')
            ->modalSubmitActionLabel('Terapkan')
            ->schema([
                Select::make('pembayaran_paket_id')
                    ->label('Paket Pembayaran')
                    ->options(fn (): array => static::paketOptions())
                    ->searchable()
                    ->required(),

                Select::make('siswa_id')
                    ->label('Siswa')
                    ->options(fn (): array => static::siswaOptions())
                    ->searchable()
                    ->required(),

                Select::make('semester_id')
                    ->label('Semester')
                    ->options(fn (): array => static::semesterOptions())
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data): void {
                $paket = PembayaranPaket::findOrFail($data['pembayaran_paket_id']);
                $siswa = Siswa::findOrFail($data['siswa_id']);
                $semester = Semester::findOrFail($data['semester_id']);

                $hasil = app(GeneratorTagihanService::class)->terapkanPaket(
                    $paket,
                    $siswa,
                    $semester,
                );

                Notification::make()
                    ->title("{$hasil['dibuat']} tagihan dibuat, {$hasil['dilewati']} dilewati")
                    ->success()
                    ->send();
            });
    }

    /**
     * Opsi jenis pembayaran, jenis 'bulanan' diutamakan tampil lebih dulu.
     *
     * @return array<int|string, string>
     */
    private static function jenisPembayaranOptions(): array
    {
        return JenisPembayaran::query()
            ->orderByRaw("CASE WHEN jenis = 'bulanan' THEN 0 ELSE 1 END")
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn (JenisPembayaran $jenis): array => [
                $jenis->getKey() => "{$jenis->kode} - {$jenis->nama} ({$jenis->jenis_info})",
            ])
            ->all();
    }

    /**
     * @return array<int|string, string>
     */
    private static function semesterOptions(): array
    {
        return Semester::query()
            ->with('tahunAjaran')
            ->ordered()
            ->get()
            ->mapWithKeys(fn (Semester $semester): array => [
                $semester->getKey() => "{$semester->tahunAjaran?->nama} - {$semester->nama}",
            ])
            ->all();
    }

    /**
     * @return array<int|string, string>
     */
    private static function kelasOptions(): array
    {
        return Kelas::query()
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->all();
    }

    /**
     * @return array<int|string, string>
     */
    private static function paketOptions(): array
    {
        return PembayaranPaket::query()
            ->orderBy('nama')
            ->pluck('nama', 'id')
            ->all();
    }

    /**
     * @return array<int|string, string>
     */
    private static function siswaOptions(): array
    {
        return Siswa::query()
            ->where('status', 'aktif')
            ->where('is_active', true)
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn (Siswa $siswa): array => [
                $siswa->getKey() => "{$siswa->nis} - {$siswa->nama}",
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private static function bulanOptions(): array
    {
        return [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
    }
}
