<?php

namespace App\Filament\Resources\MutasiBanks\Pages;

use App\Filament\Resources\MutasiBanks\MutasiBankResource;
use App\Models\MutasiBank;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListMutasiBanks extends ListRecords
{
    protected static string $resource = MutasiBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            $this->importAction(),
        ];
    }

    /**
     * Import sederhana mutasi rekening koran: tempel baris teks (satu baris per
     * mutasi) dengan format `tanggal;keterangan;debit;kredit`, pemisah `;`, `,`,
     * atau TAB. Tanggal menerima format Y-m-d atau d/m/Y. Setiap baris valid
     * tersimpan sebagai MutasiBank dengan is_matched = false (outstanding).
     */
    private function importAction(): Action
    {
        return Action::make('importMutasi')
            ->label('Import Mutasi')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->form([
                Select::make('akun_id')
                    ->label('Akun Bank')
                    ->options(fn (): array => MutasiBankResource::bankAkunOptions())
                    ->searchable()
                    ->preload()
                    ->required(),

                Textarea::make('data')
                    ->label('Baris Rekening Koran')
                    ->helperText('Satu baris per mutasi: tanggal;keterangan;debit;kredit (pemisah ; , atau TAB). Contoh: 2026-07-01;Setoran tunai;1000000;0')
                    ->rows(8)
                    ->required(),
            ])
            ->action(function (array $data): void {
                $akunId = (int) $data['akun_id'];
                $baris = static::parseMutasi((string) $data['data']);

                if ($baris === []) {
                    Notification::make()
                        ->title('Tidak ada baris valid untuk diimport')
                        ->warning()
                        ->send();

                    return;
                }

                foreach ($baris as $row) {
                    MutasiBank::create([
                        'akun_id' => $akunId,
                        'tanggal' => $row['tanggal'],
                        'keterangan' => $row['keterangan'],
                        'debit' => $row['debit'],
                        'kredit' => $row['kredit'],
                        'is_matched' => false,
                    ]);
                }

                Notification::make()
                    ->title(count($baris).' baris mutasi diimport')
                    ->success()
                    ->send();
            });
    }

    /**
     * Parse teks rekening koran menjadi baris terstruktur. Baris kosong atau yang
     * tidak punya tanggal valid dilewati.
     *
     * @return array<int, array{tanggal: string, keterangan: string|null, debit: string, kredit: string}>
     */
    public static function parseMutasi(string $teks): array
    {
        $hasil = [];

        foreach (preg_split('/\r\n|\r|\n/', $teks) ?: [] as $baris) {
            $baris = trim($baris);

            if ($baris === '') {
                continue;
            }

            $kolom = preg_split('/[;,\t]/', $baris) ?: [];
            $tanggalMentah = trim($kolom[0] ?? '');

            if ($tanggalMentah === '') {
                continue;
            }

            try {
                $tanggal = Carbon::parse($tanggalMentah)->toDateString();
            } catch (\Throwable) {
                continue;
            }

            $hasil[] = [
                'tanggal' => $tanggal,
                'keterangan' => trim($kolom[1] ?? '') !== '' ? trim($kolom[1]) : null,
                'debit' => static::angka($kolom[2] ?? '0'),
                'kredit' => static::angka($kolom[3] ?? '0'),
            ];
        }

        return $hasil;
    }

    /**
     * Bersihkan string angka (buang pemisah ribuan titik/spasi) menjadi nilai
     * desimal yang aman disimpan.
     */
    private static function angka(string $nilai): string
    {
        $bersih = preg_replace('/[^0-9.]/', '', str_replace([' ', '.'], ['', ''], trim($nilai))) ?? '0';

        return $bersih === '' ? '0' : $bersih;
    }
}
