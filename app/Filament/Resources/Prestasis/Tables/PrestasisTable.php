<?php

namespace App\Filament\Resources\Prestasis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PrestasisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make("siswa.nama")
                    ->label("Siswa")
                    ->searchable()
                    ->sortable(),

                TextColumn::make("nama_prestasi")
                    ->label("Prestasi")
                    ->searchable()
                    ->wrap(),

                TextColumn::make("tingkat")
                    ->label("Tingkat")
                    ->badge()
                    ->formatStateUsing(
                        fn(string $state) => match ($state) {
                            "sekolah" => "Sekolah",
                            "kecamatan" => "Kecamatan",
                            "kabupaten" => "Kabupaten",
                            "provinsi" => "Provinsi",
                            "nasional" => "Nasional",
                            "internasional" => "Internasional",
                            default => $state,
                        },
                    )
                    ->color(
                        fn(string $state) => match ($state) {
                            "nasional", "internasional" => "success",
                            "provinsi" => "warning",
                            default => "info",
                        },
                    ),

                TextColumn::make("jenis")
                    ->label("Jenis")
                    ->badge()
                    ->toggleable(),

                TextColumn::make("peringkat")
                    ->label("Peringkat")
                    ->badge()
                    ->color("success")
                    ->toggleable(),

                TextColumn::make("tanggal")
                    ->label("Tanggal")
                    ->date("d M Y")
                    ->sortable(),

                TextColumn::make("semester.nama")
                    ->label("Semester")
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make("tingkat")->options([
                    "sekolah" => "Sekolah",
                    "kecamatan" => "Kecamatan",
                    "kabupaten" => "Kabupaten",
                    "provinsi" => "Provinsi",
                    "nasional" => "Nasional",
                    "internasional" => "Internasional",
                ]),
                SelectFilter::make("jenis")->options([
                    "akademik" => "Akademik",
                    "non_akademik" => "Non Akademik",
                    "olahraga" => "Olahraga",
                    "seni" => "Seni",
                    "keagamaan" => "Keagamaan",
                    "lainnya" => "Lainnya",
                ]),
                SelectFilter::make("semester_id")
                    ->label("Semester")
                    ->relationship("semester", "nama"),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort("tanggal", "desc");
    }
}
