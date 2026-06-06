<?php

namespace App\Filament\Resources\SaldoAwals\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class SaldoAwalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('akun_id')
                    ->relationship('akun', 'nama')
                    ->label('Akun')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(
                        table: 'saldo_awals',
                        column: 'akun_id',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('tahun_ajaran_id', $get('tahun_ajaran_id')),
                    ),
                Select::make('tahun_ajaran_id')
                    ->relationship('tahunAjaran', 'nama')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('saldo')
                    ->label('Saldo')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),
                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->default(now()),
                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
