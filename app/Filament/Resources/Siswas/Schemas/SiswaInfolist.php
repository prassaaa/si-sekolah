<?php

namespace App\Filament\Resources\Siswas\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class SiswaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Data Siswa')
                    ->tabs([
                        Tab::make('Identitas')
                            ->icon('heroicon-o-user')
                            ->schema(self::identitasSchema()),

                        Tab::make('Alamat & Kontak')
                            ->icon('heroicon-o-map-pin')
                            ->schema(self::alamatKontakSchema()),

                        Tab::make('Akademik')
                            ->icon('heroicon-o-academic-cap')
                            ->schema(self::akademikSchema()),

                        Tab::make('Kesehatan')
                            ->icon('heroicon-o-heart')
                            ->schema(self::kesehatanSchema()),

                        Tab::make('Orang Tua')
                            ->icon('heroicon-o-users')
                            ->schema(self::orangTuaSchema()),

                        Tab::make('Wali')
                            ->icon('heroicon-o-user-plus')
                            ->schema(self::waliSchema()),

                        Tab::make('Dokumen')
                            ->icon('heroicon-o-document')
                            ->schema(self::dokumenSchema()),
                    ])
                    ->columnSpanFull(),

                Section::make('Informasi Sistem')
                    ->collapsed()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('created_at')
                                ->label('Dibuat')
                                ->dateTime('d M Y H:i'),
                            TextEntry::make('updated_at')
                                ->label('Diperbarui')
                                ->dateTime('d M Y H:i'),
                            TextEntry::make('deleted_at')
                                ->label('Dihapus')
                                ->dateTime('d M Y H:i')
                                ->placeholder('-'),
                        ]),
                    ]),
            ]);
    }

    /**
     * @return array<int, \Filament\Infolists\Components\Component>
     */
    private static function identitasSchema(): array
    {
        return [
            Section::make('Nomor Induk')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('nis')
                            ->label('NIS')
                            ->weight(FontWeight::Bold)
                            ->copyable(),
                        TextEntry::make('nisn')
                            ->label('NISN')
                            ->copyable()
                            ->placeholder('-'),
                        TextEntry::make('nik')
                            ->label('NIK')
                            ->copyable()
                            ->placeholder('-'),
                    ]),
                ]),

            Section::make('Data Pribadi')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('nama')
                            ->label('Nama Lengkap')
                            ->weight(FontWeight::Bold)
                            ->size('lg'),
                        TextEntry::make('nama_panggilan')
                            ->label('Nama Panggilan')
                            ->placeholder('-'),
                        TextEntry::make('jenis_kelamin_label')
                            ->label('Jenis Kelamin')
                            ->badge()
                            ->color(fn ($record) => $record->jenis_kelamin === 'L' ? 'info' : 'danger'),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('ttl')
                            ->label('Tempat, Tanggal Lahir'),
                        TextEntry::make('usia')
                            ->label('Usia')
                            ->suffix(' tahun')
                            ->placeholder('-'),
                        TextEntry::make('agama')
                            ->label('Agama')
                            ->placeholder('-'),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('no_kk')
                            ->label('No. Kartu Keluarga')
                            ->placeholder('-'),
                        TextEntry::make('no_akta')
                            ->label('No. Akta Kelahiran')
                            ->placeholder('-'),
                        TextEntry::make('kewarganegaraan')
                            ->label('Kewarganegaraan'),
                    ]),
                    Grid::make(2)->schema([
                        TextEntry::make('anak_ke')
                            ->label('Anak Ke')
                            ->placeholder('-'),
                        TextEntry::make('jumlah_saudara')
                            ->label('Jumlah Saudara')
                            ->placeholder('-'),
                    ]),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Infolists\Components\Component>
     */
    private static function alamatKontakSchema(): array
    {
        return [
            Section::make('Alamat')
                ->schema([
                    TextEntry::make('alamat_lengkap')
                        ->label('Alamat Lengkap')
                        ->columnSpanFull(),
                ]),

            Section::make('Kontak')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('telepon')
                            ->label('Telepon')
                            ->placeholder('-'),
                        TextEntry::make('hp')
                            ->label('No. HP')
                            ->copyable()
                            ->placeholder('-'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->copyable()
                            ->placeholder('-'),
                    ]),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Infolists\Components\Component>
     */
    private static function akademikSchema(): array
    {
        return [
            Section::make('Data Akademik')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('kelas.nama')
                            ->label('Kelas')
                            ->badge()
                            ->color('success')
                            ->placeholder('Belum ada kelas'),
                        TextEntry::make('tanggal_masuk')
                            ->label('Tanggal Masuk')
                            ->date('d F Y')
                            ->placeholder('-'),
                        TextEntry::make('tahun_masuk')
                            ->label('Tahun Masuk')
                            ->placeholder('-'),
                    ]),
                    Grid::make(2)->schema([
                        TextEntry::make('asal_sekolah')
                            ->label('Asal Sekolah')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($record) => $record->status_info['label'])
                            ->color(fn ($record) => $record->status_info['color']),
                    ]),
                    Grid::make(2)->schema([
                        TextEntry::make('is_active')
                            ->label('Status Aktif')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Tidak Aktif')
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('catatan')
                            ->label('Catatan')
                            ->placeholder('-'),
                    ]),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Infolists\Components\Component>
     */
    private static function kesehatanSchema(): array
    {
        return [
            Section::make('Data Kesehatan')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('golongan_darah')
                            ->label('Golongan Darah')
                            ->badge()
                            ->color('danger')
                            ->placeholder('-'),
                        TextEntry::make('tinggi_badan')
                            ->label('Tinggi Badan')
                            ->suffix(' cm')
                            ->placeholder('-'),
                        TextEntry::make('berat_badan')
                            ->label('Berat Badan')
                            ->suffix(' kg')
                            ->placeholder('-'),
                    ]),
                    TextEntry::make('riwayat_penyakit')
                        ->label('Riwayat Penyakit')
                        ->placeholder('-')
                        ->columnSpanFull(),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Infolists\Components\Component>
     */
    private static function orangTuaSchema(): array
    {
        return [
            Section::make('Data Ayah')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('nama_ayah')
                            ->label('Nama')
                            ->weight(FontWeight::Bold)
                            ->placeholder('-'),
                        TextEntry::make('nik_ayah')
                            ->label('NIK')
                            ->placeholder('-'),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('pendidikan_ayah')
                            ->label('Pendidikan')
                            ->placeholder('-'),
                        TextEntry::make('pekerjaan_ayah')
                            ->label('Pekerjaan')
                            ->placeholder('-'),
                        TextEntry::make('penghasilan_ayah')
                            ->label('Penghasilan')
                            ->money('IDR')
                            ->placeholder('-'),
                    ]),
                    Grid::make(2)->schema([
                        TextEntry::make('telepon_ayah')
                            ->label('Telepon')
                            ->copyable()
                            ->placeholder('-'),
                        TextEntry::make('alamat_ayah')
                            ->label('Alamat')
                            ->placeholder('-'),
                    ]),
                ]),

            Section::make('Data Ibu')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('nama_ibu')
                            ->label('Nama')
                            ->weight(FontWeight::Bold)
                            ->placeholder('-'),
                        TextEntry::make('nik_ibu')
                            ->label('NIK')
                            ->placeholder('-'),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('pendidikan_ibu')
                            ->label('Pendidikan')
                            ->placeholder('-'),
                        TextEntry::make('pekerjaan_ibu')
                            ->label('Pekerjaan')
                            ->placeholder('-'),
                        TextEntry::make('penghasilan_ibu')
                            ->label('Penghasilan')
                            ->money('IDR')
                            ->placeholder('-'),
                    ]),
                    Grid::make(2)->schema([
                        TextEntry::make('telepon_ibu')
                            ->label('Telepon')
                            ->copyable()
                            ->placeholder('-'),
                        TextEntry::make('alamat_ibu')
                            ->label('Alamat')
                            ->placeholder('-'),
                    ]),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Infolists\Components\Component>
     */
    private static function waliSchema(): array
    {
        return [
            Section::make('Data Wali')
                ->description('Wali siswa jika berbeda dengan orang tua')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('nama_wali')
                            ->label('Nama')
                            ->weight(FontWeight::Bold)
                            ->placeholder('-'),
                        TextEntry::make('nik_wali')
                            ->label('NIK')
                            ->placeholder('-'),
                        TextEntry::make('hubungan_wali')
                            ->label('Hubungan')
                            ->placeholder('-'),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('pendidikan_wali')
                            ->label('Pendidikan')
                            ->placeholder('-'),
                        TextEntry::make('pekerjaan_wali')
                            ->label('Pekerjaan')
                            ->placeholder('-'),
                        TextEntry::make('penghasilan_wali')
                            ->label('Penghasilan')
                            ->money('IDR')
                            ->placeholder('-'),
                    ]),
                    Grid::make(2)->schema([
                        TextEntry::make('telepon_wali')
                            ->label('Telepon')
                            ->copyable()
                            ->placeholder('-'),
                        TextEntry::make('alamat_wali')
                            ->label('Alamat')
                            ->placeholder('-'),
                    ]),
                ]),
        ];
    }

    /**
     * @return array<int, \Filament\Infolists\Components\Component>
     */
    private static function dokumenSchema(): array
    {
        return [
            Section::make('Dokumen')
                ->schema([
                    Grid::make(2)->schema([
                        ImageEntry::make('foto')
                            ->label('Foto Siswa')
                            ->circular()
                            ->size(150),
                        ImageEntry::make('foto_kk')
                            ->label('Kartu Keluarga')
                            ->size(150),
                    ]),
                    Grid::make(2)->schema([
                        ImageEntry::make('foto_akta')
                            ->label('Akta Kelahiran')
                            ->size(150),
                        ImageEntry::make('foto_ijazah')
                            ->label('Ijazah')
                            ->size(150),
                    ]),
                ]),
        ];
    }
}
