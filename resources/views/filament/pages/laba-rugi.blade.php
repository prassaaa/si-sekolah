<x-filament-panels::page>
    <form wire:submit="filter">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Tampilkan
            </x-filament::button>
        </div>
    </form>

    <div class="grid gap-6 lg:grid-cols-2 mt-6">
        <x-filament::section>
            <x-slot name="heading">Pendapatan</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <tbody>
                        @foreach($pendapatan as $item)
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2 px-4">{{ $item['akun'] }}</td>
                                <td class="py-2 px-4 text-right">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 font-bold dark:border-gray-600">
                            <td class="py-2 px-4">Total Pendapatan</td>
                            <td class="py-2 px-4 text-right text-success-600">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Beban</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <tbody>
                        @foreach($beban as $item)
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2 px-4">{{ $item['akun'] }}</td>
                                <td class="py-2 px-4 text-right">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 font-bold dark:border-gray-600">
                            <td class="py-2 px-4">Total Beban</td>
                            <td class="py-2 px-4 text-right text-danger-600">Rp {{ number_format($totalBeban, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>
    </div>

    <x-filament::section class="mt-6">
        <div class="flex justify-between items-center">
            <span class="text-xl font-bold">{{ $labaRugi >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}</span>
            <span class="text-2xl font-bold {{ $labaRugi >= 0 ? 'text-success-600' : 'text-danger-600' }}">Rp {{ number_format(abs($labaRugi), 0, ',', '.') }}</span>
        </div>
    </x-filament::section>
</x-filament-panels::page>
