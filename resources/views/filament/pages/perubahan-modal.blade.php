<x-filament-panels::page>
    <form wire:submit="filter">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Tampilkan
            </x-filament::button>
        </div>
    </form>

    <x-filament::section class="mt-6">
        <x-slot name="heading">Laporan Perubahan Modal</x-slot>

        <div style="overflow-x: auto">
            <table class="w-full text-left">
                <tbody>
                    <tr class="border-b dark:border-gray-700">
                        <td class="py-3 px-4">Modal Awal</td>
                        <td class="py-3 px-4 text-right font-semibold">Rp {{ number_format($modalAwal, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="border-b dark:border-gray-700">
                        <td class="py-3 px-4">{{ $labaRugi >= 0 ? 'Laba Bersih' : 'Rugi Bersih' }}</td>
                        <td class="py-3 px-4 text-right font-semibold {{ $labaRugi >= 0 ? 'text-success-600' : 'text-danger-600' }}">Rp {{ number_format($labaRugi, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="border-b dark:border-gray-700">
                        <td class="py-3 px-4">Prive / Pengambilan Pemilik</td>
                        <td class="py-3 px-4 text-right font-semibold text-danger-600">(Rp {{ number_format($prive, 0, ',', '.') }})</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="border-t-2 dark:border-gray-600">
                        <td class="py-3 px-4 text-lg font-bold">Modal Akhir</td>
                        <td class="py-3 px-4 text-right text-2xl font-bold text-primary-600">Rp {{ number_format($modalAkhir, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
