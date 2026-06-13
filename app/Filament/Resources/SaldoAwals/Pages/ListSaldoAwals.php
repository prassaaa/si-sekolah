<?php

namespace App\Filament\Resources\SaldoAwals\Pages;

use App\Filament\Resources\SaldoAwals\SaldoAwalResource;
use App\Models\TahunAjaran;
use App\Services\Accounting\RollForwardSaldoAwalService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSaldoAwals extends ListRecords
{
    protected static string $resource = SaldoAwalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            $this->generateDariTaSebelumnyaAction(),
        ];
    }

    private function generateDariTaSebelumnyaAction(): Action
    {
        return Action::make('generateDariTaSebelumnya')
            ->label('Generate dari TA Sebelumnya')
            ->icon(Heroicon::OutlinedArrowPath)
            ->visible(fn (): bool => auth()->user()?->can('Create:SaldoAwal') ?? false)
            ->modalHeading('Generate Saldo Awal dari Tahun Ajaran Sebelumnya')
            ->modalDescription('Saldo akhir tiap akun riil TA lama menjadi saldo awal TA baru; laba/rugi TA lama ditutup ke Laba Ditahan. Aman dijalankan ulang (idempoten).')
            ->form([
                Select::make('ta_lama_id')
                    ->label('Tahun Ajaran Sumber (lama)')
                    ->options(fn (): array => TahunAjaran::query()->ordered()->pluck('nama', 'id')->all())
                    ->required()
                    ->different('ta_baru_id'),
                Select::make('ta_baru_id')
                    ->label('Tahun Ajaran Tujuan (baru)')
                    ->options(fn (): array => TahunAjaran::query()->ordered()->pluck('nama', 'id')->all())
                    ->required()
                    ->different('ta_lama_id'),
            ])
            ->action(function (array $data, RollForwardSaldoAwalService $service): void {
                $taLama = TahunAjaran::findOrFail($data['ta_lama_id']);
                $taBaru = TahunAjaran::findOrFail($data['ta_baru_id']);

                $ringkasan = $service->generate($taLama, $taBaru);

                Notification::make()
                    ->title('Saldo awal berhasil di-generate')
                    ->body("{$ringkasan['akun_diproses']} akun diproses. Laba ditahan bertambah Rp ".number_format((float) $ringkasan['laba_ditahan_ditambah'], 2, ',', '.').'.')
                    ->success()
                    ->send();
            });
    }
}
