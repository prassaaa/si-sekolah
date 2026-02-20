<?php

namespace App\Filament\Resources\Absensis\Schemas;

use App\Models\Absensi;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class AbsensiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Data Absensi')->icon('heroicon-o-clipboard-document-check')->schema([
                Grid::make(3)->schema([
                    TextEntry::make('tanggal')->label('Tanggal')->date('d M Y')->weight(FontWeight::Bold),
                    TextEntry::make('siswa.nama_lengkap')->label('Siswa'),
                    TextEntry::make('jadwalPelajaran.kelas.nama')->label('Kelas')->badge()->color('success'),
                ]),
                Grid::make(3)->schema([
                    TextEntry::make('jadwalPelajaran.mataPelajaran.nama')->label('Mata Pelajaran')->weight(FontWeight::Bold),
                    TextEntry::make('jadwalPelajaran.jadwal_lengkap')->label('Jadwal'),
                    TextEntry::make('status')->label('Status')->badge()
                        ->formatStateUsing(fn (string $state): string => Absensi::statusOptions()[$state] ?? $state)
                        ->color(fn (string $state): string => Absensi::statusColors()[$state] ?? 'gray'),
                ]),
                TextEntry::make('keterangan')->label('Keterangan')->placeholder('-')->columnSpanFull(),
            ]),

            Section::make('Informasi Sistem')->collapsed()->schema([
                Grid::make(2)->schema([
                    TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                    TextEntry::make('updated_at')->label('Diperbarui')->dateTime('d M Y H:i'),
                ]),
            ]),
        ]);
    }
}
