<x-filament-panels::page>
    <form wire:submit="filter">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Tampilkan
            </x-filament::button>
        </div>
    </form>

    @if(count($entries) > 0)
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Buku Besar
            </x-slot>

            <div class="mb-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Saldo Awal: <span class="font-semibold">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</span>
                </p>
            </div>

            <div style="overflow-x: auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b dark:border-gray-700">
                            <th class="py-2 px-4">Tanggal</th>
                            <th class="py-2 px-4">Keterangan</th>
                            <th class="py-2 px-4 text-right">Debit</th>
                            <th class="py-2 px-4 text-right">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2 px-4">{{ $entry['tanggal'] }}</td>
                                <td class="py-2 px-4">{{ $entry['keterangan'] }}</td>
                                <td class="py-2 px-4 text-right">Rp {{ number_format($entry['debit'], 0, ',', '.') }}</td>
                                <td class="py-2 px-4 text-right">Rp {{ number_format($entry['kredit'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 font-bold dark:border-gray-600">
                            <td colspan="2" class="py-2 px-4">Saldo Akhir</td>
                            <td colspan="2" class="py-2 px-4 text-right text-primary-600">Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
