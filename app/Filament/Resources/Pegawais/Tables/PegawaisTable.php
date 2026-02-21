<?php

namespace App\Filament\Resources\Pegawais\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PegawaisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make("foto")
                    ->label("")
                    ->circular()
                    ->imageSize(40)
                    ->defaultImageUrl(
                        fn(
                            $record,
                        ): string => "https://ui-avatars.com/api/?name=" .
                            urlencode($record->nama) .
                            "&size=40&background=random",
                    ),
                TextColumn::make("nip")
                    ->label("NIP")
                    ->searchable()
                    ->copyable()
                    ->placeholder("-")
                    ->toggleable(),
                TextColumn::make("nama")
                    ->label("Nama Lengkap")
                    ->searchable()
                    ->sortable()
                    ->weight("bold")
                    ->description(
                        fn($record): string => $record->jabatan?->nama ?? "-",
                    ),
                TextColumn::make("jabatan.nama")
                    ->label("Jabatan")
                    ->sortable()
                    ->badge()
                    ->color("primary")
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("status_kepegawaian")
                    ->label("Status")
                    ->badge()
                    ->color(
                        fn(string $state): string => match ($state) {
                            "PNS" => "success",
                            "PPPK" => "info",
                            "GTY" => "primary",
                            "GTT" => "warning",
                            "PTY" => "gray",
                            "PTT" => "gray",
                            default => "gray",
                        },
                    ),
                TextColumn::make("jenis_kelamin")
                    ->label("L/P")
                    ->formatStateUsing(fn(string $state): string => $state)
                    ->badge()
                    ->color(
                        fn(string $state): string => $state === "L"
                            ? "info"
                            : "pink",
                    )
                    ->toggleable(),
                TextColumn::make("telepon")
                    ->label("Telepon")
                    ->searchable()
                    ->copyable()
                    ->placeholder("-")
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("email")
                    ->label("Email")
                    ->searchable()
                    ->copyable()
                    ->placeholder("-")
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make("pendidikan_terakhir")
                    ->label("Pendidikan")
                    ->badge()
                    ->color("gray")
                    ->toggleable(),
                TextColumn::make("tanggal_masuk")
                    ->label("Mulai Kerja")
                    ->date("d/m/Y")
                    ->sortable()
                    ->toggleable(),
                TextColumn::make("masa_kerja")
                    ->label("Masa Kerja")
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make("is_active")->label("Aktif")->sortable(),
            ])
            ->defaultSort("nama", "asc")
            ->filters([
                SelectFilter::make("jabatan_id")
                    ->label("Jabatan")
                    ->relationship("jabatan", "nama")
                    ->searchable()
                    ->preload(),
                SelectFilter::make("status_kepegawaian")
                    ->label("Status Kepegawaian")
                    ->options([
                        "PNS" => "PNS",
                        "PPPK" => "PPPK",
                        "GTY" => "GTY",
                        "GTT" => "GTT",
                        "PTY" => "PTY",
                        "PTT" => "PTT",
                    ]),
                SelectFilter::make("jenis_kelamin")
                    ->label("Jenis Kelamin")
                    ->options([
                        "L" => "Laki-laki",
                        "P" => "Perempuan",
                    ]),
                SelectFilter::make("pendidikan_terakhir")
                    ->label("Pendidikan")
                    ->options([
                        "SD" => "SD",
                        "SMP" => "SMP",
                        "SMA" => "SMA",
                        "D1" => "D1",
                        "D2" => "D2",
                        "D3" => "D3",
                        "D4" => "D4",
                        "S1" => "S1",
                        "S2" => "S2",
                        "S3" => "S3",
                    ]),
                TernaryFilter::make("is_active")
                    ->label("Status Aktif")
                    ->trueLabel("Aktif")
                    ->falseLabel("Non-Aktif"),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
