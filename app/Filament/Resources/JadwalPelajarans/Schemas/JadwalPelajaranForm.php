<?php

namespace App\Filament\Resources\JadwalPelajarans\Schemas;

use App\Models\JadwalPelajaran;
use App\Models\JamPelajaran;
use App\Models\Semester;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class JadwalPelajaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Jadwal Pelajaran')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('semester_id')
                                ->label('Semester')
                                ->relationship('semester', 'nama')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default(fn () => Semester::where('is_active', true)->first()?->id),
                            Select::make('kelas_id')
                                ->label('Kelas')
                                ->relationship('kelas', 'nama')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('hari')
                                ->label('Hari')
                                ->required()
                                ->native(false)
                                ->options(JadwalPelajaran::hariOptions()),
                            Select::make('jam_pelajaran_id')
                                ->label('Jam Pelajaran')
                                ->options(fn () => JamPelajaran::ordered()->get()->mapWithKeys(fn ($j) => [$j->id => $j->label]))
                                ->searchable()
                                ->required()
                                ->unique(
                                    table: 'jadwal_pelajarans',
                                    column: 'jam_pelajaran_id',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                                        ->where('semester_id', $get('semester_id'))
                                        ->where('kelas_id', $get('kelas_id'))
                                        ->where('hari', $get('hari')),
                                ),
                        ]),
                        Grid::make(2)->schema([
                            Select::make('mata_pelajaran_id')
                                ->label('Mata Pelajaran')
                                ->relationship('mataPelajaran', 'nama')
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('guru_id')
                                ->label('Guru')
                                ->relationship('guru', 'nama')
                                ->searchable()
                                ->preload()
                                ->placeholder('Pilih guru')
                                ->rule(fn (Get $get) => function ($attribute, $value, $fail) use ($get) {
                                    if (! $value) {
                                        return;
                                    }

                                    $exists = JadwalPelajaran::query()
                                        ->where('guru_id', $value)
                                        ->where('hari', $get('hari'))
                                        ->where('jam_pelajaran_id', $get('jam_pelajaran_id'))
                                        ->when(
                                            request()->route('record'),
                                            fn ($q) => $q->where('id', '!=', request()->route('record'))
                                        )
                                        ->exists();

                                    if ($exists) {
                                        $fail('Guru ini sudah dijadwalkan pada hari dan jam yang sama.');
                                    }
                                }),
                        ]),
                    ]),

                Section::make('Pengaturan')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
