<x-filament-panels::page>
    <form wire:submit="filter">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Tampilkan
            </x-filament::button>
        </div>
    </form>

    <div class="grid gap-4 md:grid-cols-3 mt-6">
        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Kas Masuk</p>
                <p class="text-2xl font-bold text-success-600">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Kas Keluar</p>
                <p class="text-2xl font-bold text-danger-600">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Selisih</p>
                <p class="text-2xl font-bold {{ $selisih >= 0 ? 'text-success-600' : 'text-danger-600' }}">Rp {{ number_format($selisih, 0, ',', '.') }}</p>
            </div>
        </x-filament::section>
    </div>

    @if(count($kasMasuk) > 0 || count($kasKeluar) > 0)
        <div class="grid gap-6 lg:grid-cols-2 mt-6">
            <x-filament::section>
                <x-slot name="heading">Kas Masuk</x-slot>
                <div style="overflow-x: auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="py-2 px-2">Tanggal</th>
                                <th class="py-2 px-2">Sumber</th>
                                <th class="py-2 px-2 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kasMasuk as $item)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="py-2 px-2">{{ $item['tanggal'] }}</td>
                                    <td class="py-2 px-2">{{ $item['sumber'] ?? '-' }}</td>
                                    <td class="py-2 px-2 text-right">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Kas Keluar</x-slot>
                <div style="overflow-x: auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b dark:border-gray-700">
                                <th class="py-2 px-2">Tanggal</th>
                                <th class="py-2 px-2">Penerima</th>
                                <th class="py-2 px-2 text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kasKeluar as $item)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="py-2 px-2">{{ $item['tanggal'] }}</td>
                                    <td class="py-2 px-2">{{ $item['penerima'] ?? '-' }}</td>
                                    <td class="py-2 px-2 text-right">Rp {{ number_format($item['nominal'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
