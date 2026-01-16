<?php

namespace App\Filament\Resources\Informasis\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class InformasiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konten Informasi')
                    ->schema([
                        TextInput::make('judul')
                            ->label('Judul')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('kategori')
                            ->label('Kategori')
                            ->options([
                                'Pengumuman' => 'Pengumuman',
                                'Berita' => 'Berita',
                                'Kegiatan' => 'Kegiatan',
                                'Prestasi' => 'Prestasi',
                                'Lainnya' => 'Lainnya',
                            ])
                            ->default('Pengumuman')
                            ->required()
                            ->searchable(),
                        Select::make('prioritas')
                            ->label('Prioritas')
                            ->options([
                                'Rendah' => 'Rendah',
                                'Normal' => 'Normal',
                                'Tinggi' => 'Tinggi',
                                'Urgent' => 'Urgent',
                            ])
                            ->default('Normal')
                            ->required(),
                        Textarea::make('ringkasan')
                            ->label('Ringkasan')
                            ->rows(3)
                            ->columnSpanFull(),
                        RichEditor::make('konten')
                            ->label('Konten')
                            ->required()
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'orderedList',
                                'unorderedList',
                                'h2',
                                'h3',
                                'blockquote',
                                'redo',
                                'undo',
                            ]),
                    ])
                    ->columns(2),

                Section::make('Media')
                    ->schema([
                        FileUpload::make('gambar')
                            ->label('Gambar')
                            ->image()
                            ->directory('informasi')
                            ->maxSize(2048)
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9'),
                    ]),

                Section::make('Pengaturan Publikasi')
                    ->schema([
                        DatePicker::make('tanggal_publish')
                            ->label('Tanggal Publish')
                            ->default(now()),
                        DatePicker::make('tanggal_expired')
                            ->label('Tanggal Expired')
                            ->after('tanggal_publish'),
                        Toggle::make('is_published')
                            ->label('Publikasikan')
                            ->default(false),
                        Toggle::make('is_pinned')
                            ->label('Sematkan (Pin)')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }
}
