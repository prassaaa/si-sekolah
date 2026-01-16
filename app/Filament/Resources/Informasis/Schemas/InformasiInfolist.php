<?php

namespace App\Filament\Resources\Informasis\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InformasiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konten Informasi')
                    ->schema([
                        TextEntry::make('judul')
                            ->label('Judul'),
                        TextEntry::make('slug')
                            ->label('Slug')
                            ->badge(),
                        TextEntry::make('kategori')
                            ->label('Kategori')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Pengumuman' => 'primary',
                                'Berita' => 'info',
                                'Kegiatan' => 'success',
                                'Prestasi' => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('prioritas')
                            ->label('Prioritas')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Urgent' => 'danger',
                                'Tinggi' => 'warning',
                                'Normal' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('ringkasan')
                            ->label('Ringkasan')
                            ->columnSpanFull()
                            ->default('-'),
                        TextEntry::make('konten')
                            ->label('Konten')
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Media')
                    ->schema([
                        ImageEntry::make('gambar')
                            ->label('Gambar')
                            ->size(300),
                    ]),

                Section::make('Pengaturan Publikasi')
                    ->schema([
                        TextEntry::make('tanggal_publish')
                            ->label('Tanggal Publish')
                            ->date('d F Y')
                            ->default('-'),
                        TextEntry::make('tanggal_expired')
                            ->label('Tanggal Expired')
                            ->date('d F Y')
                            ->default('-'),
                        IconEntry::make('is_published')
                            ->label('Status Publikasi')
                            ->boolean(),
                        IconEntry::make('is_pinned')
                            ->label('Disematkan')
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make('Informasi Tambahan')
                    ->schema([
                        TextEntry::make('createdBy.name')
                            ->label('Dibuat Oleh')
                            ->default('-'),
                        TextEntry::make('views_count')
                            ->label('Jumlah Dilihat')
                            ->numeric()
                            ->suffix(' kali'),
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d F Y H:i'),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime('d F Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }
}
