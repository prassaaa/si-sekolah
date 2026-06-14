<?php

namespace App\Filament\Pages;

use App\Jobs\KirimTagihanWaJob;
use App\Models\Kelas;
use App\Models\NotifikasiTagihan;
use App\Models\Semester;
use App\Models\TagihanSiswa;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class KirimTagihan extends Page implements HasSchemas, HasTable
{
    use ExposesTableToWidgets, HasPageShield, InteractsWithSchemas, InteractsWithTable;

    public ?string $activeTab = null;

    public ?Model $parentRecord = null;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static \UnitEnum|string|null $navigationGroup = 'Notifikasi';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Kirim Tagihan';

    protected static ?string $navigationLabel = 'Kirim Tagihan';

    protected static ?string $slug = 'notifikasi/kirim-tagihan';

    public function getTitle(): string|Htmlable
    {
        return 'Kirim Tagihan via WhatsApp';
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
                    ->whereIn('status', ['belum_bayar', 'sebagian'])
                    ->where('sisa_tagihan', '>', 0)
                    ->orderByDesc('tanggal_jatuh_tempo')
            )
            ->columns([
                TextColumn::make('siswa.nama')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('siswa.kelas.nama')
                    ->label('Kelas')
                    ->sortable(),
                TextColumn::make('nomor_tagihan')
                    ->label('No. Tagihan')
                    ->searchable(),
                TextColumn::make('tanggal_jatuh_tempo')
                    ->label('Jatuh Tempo')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (TagihanSiswa $record): string => $record->tanggal_jatuh_tempo && $record->tanggal_jatuh_tempo->isPast() ? 'danger' : 'warning'),
                TextColumn::make('sisa_tagihan')
                    ->label('Sisa Tagihan')
                    ->money('IDR')
                    ->alignEnd()
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('tujuan_nomor')
                    ->label('Nomor WA Tujuan')
                    ->state(fn (TagihanSiswa $record): string => self::resolveNomorWa($record)),
                TextColumn::make('status')
                    ->label('Status Tagihan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sebagian' => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'belum_bayar' => 'Belum Bayar',
                        'sebagian' => 'Sebagian',
                        default => ucfirst($state),
                    }),
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('kirimWa')
                        ->label('Kirim WA')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Kirim Notifikasi WhatsApp')
                        ->modalDescription('Pesan notifikasi tagihan akan dikirim ke nomor WhatsApp wali/orang tua siswa yang dipilih.')
                        ->action(function (Collection $records): void {
                            $pengirimId = auth()->id();
                            $driver = config('wa.driver', 'log');
                            $terkirim = 0;

                            foreach ($records as $tagihan) {
                                /** @var TagihanSiswa $tagihan */
                                $nomorTujuan = self::resolveNomorWa($tagihan);

                                if (! $nomorTujuan || $nomorTujuan === '-') {
                                    continue;
                                }

                                $pesan = self::buildPesan($tagihan);

                                $notifikasi = NotifikasiTagihan::query()->create([
                                    'tagihan_siswa_id' => $tagihan->id,
                                    'siswa_id' => $tagihan->siswa_id,
                                    'tujuan_nomor' => $nomorTujuan,
                                    'pesan' => $pesan,
                                    'status' => 'antri',
                                    'driver' => $driver,
                                    'dikirim_oleh' => $pengirimId,
                                ]);

                                KirimTagihanWaJob::dispatch($notifikasi);
                                $terkirim++;
                            }

                            if ($terkirim > 0) {
                                Notification::make()
                                    ->title("{$terkirim} notifikasi diantrekan")
                                    ->body('Pesan WhatsApp sedang diproses di background.')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Tidak ada notifikasi dikirim')
                                    ->body('Tidak ditemukan nomor WA tujuan untuk tagihan yang dipilih.')
                                    ->warning()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading('Tidak ada tagihan belum lunas')
            ->emptyStateDescription('Semua tagihan sudah lunas atau belum ada tagihan aktif.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    /**
     * Resolve nomor WA tujuan: prioritas siswa HP, lalu telepon ayah, ibu, wali.
     * Format ke 62xxx (Indonesia).
     */
    public static function resolveNomorWa(TagihanSiswa $tagihan): string
    {
        $siswa = $tagihan->siswa;

        if (! $siswa) {
            return '-';
        }

        $nomor = $siswa->hp
            ?: $siswa->telepon_ayah
            ?: $siswa->telepon_ibu
            ?: $siswa->telepon_wali
            ?: $siswa->telepon;

        if (! $nomor) {
            return '-';
        }

        return self::formatNomorIndonesia($nomor);
    }

    /**
     * Format nomor HP Indonesia: 08xx → 628xx, +628xx → 628xx.
     */
    public static function formatNomorIndonesia(string $nomor): string
    {
        $nomor = preg_replace('/\D/', '', $nomor);

        if (str_starts_with($nomor, '0')) {
            $nomor = '62'.substr($nomor, 1);
        } elseif (str_starts_with($nomor, '62') === false && str_starts_with($nomor, '8')) {
            $nomor = '62'.$nomor;
        }

        return $nomor;
    }

    /**
     * Buat teks pesan notifikasi tagihan untuk siswa.
     */
    public static function buildPesan(TagihanSiswa $tagihan): string
    {
        $siswa = $tagihan->siswa;
        $namaSiswa = $siswa?->nama ?? '-';
        $nomorTagihan = $tagihan->nomor_tagihan;
        $sisa = 'Rp '.number_format((float) $tagihan->sisa_tagihan, 0, ',', '.');
        $jatuhTempo = $tagihan->tanggal_jatuh_tempo
            ? $tagihan->tanggal_jatuh_tempo->translatedFormat('d F Y')
            : '-';

        return <<<TEXT
        Yth. Wali Murid / Orang Tua dari *{$namaSiswa}*,

        Kami memberitahukan bahwa terdapat tagihan yang belum dilunasi:

        📋 No. Tagihan : {$nomorTagihan}
        💰 Sisa Tagihan : {$sisa}
        📅 Jatuh Tempo : {$jatuhTempo}

        Mohon segera melakukan pembayaran sebelum tanggal jatuh tempo. Terima kasih atas perhatian dan kerja sama Bapak/Ibu.

        _Pesan ini dikirim otomatis oleh sistem sekolah._
        TEXT;
    }
}
