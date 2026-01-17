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
            <x-slot name="heading">Aset</x-slot>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <tbody>
                        @foreach($aset as $item)
                            <tr class="border-b dark:border-gray-700">
                                <td class="py-2 px-4">{{ $item['akun'] }}</td>
                                <td class="py-2 px-4 text-right">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 font-bold dark:border-gray-600">
                            <td class="py-2 px-4">Total Aset</td>
                            <td class="py-2 px-4 text-right text-primary-600">Rp {{ number_format($totalAset, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>

        <div class="space-y-6">
            <x-filament::section>
                <x-slot name="heading">Kewajiban</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <tbody>
                            @foreach($kewajiban as $item)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="py-2 px-4">{{ $item['akun'] }}</td>
                                    <td class="py-2 px-4 text-right">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 font-bold dark:border-gray-600">
                                <td class="py-2 px-4">Total Kewajiban</td>
                                <td class="py-2 px-4 text-right text-danger-600">Rp {{ number_format($totalKewajiban, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Modal</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <tbody>
                            @foreach($modal as $item)
                                <tr class="border-b dark:border-gray-700">
                                    <td class="py-2 px-4">{{ $item['akun'] }}</td>
                                    <td class="py-2 px-4 text-right">Rp {{ number_format($item['saldo'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 font-bold dark:border-gray-600">
                                <td class="py-2 px-4">Total Modal</td>
                                <td class="py-2 px-4 text-right text-success-600">Rp {{ number_format($totalModal, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-filament::section>
        </div>
    </div>

    <x-filament::section class="mt-6">
        <div class="flex justify-between items-center">
            <span class="text-lg font-bold">Total Kewajiban + Modal</span>
            <span class="text-xl font-bold text-primary-600">Rp {{ number_format($totalKewajiban + $totalModal, 0, ',', '.') }}</span>
        </div>
        @if($totalAset != ($totalKewajiban + $totalModal))
            <p class="text-sm text-danger-600 mt-2">⚠️ Neraca tidak seimbang!</p>
        @else
            <p class="text-sm text-success-600 mt-2">✓ Neraca seimbang</p>
        @endif
    </x-filament::section>
</x-filament-panels::page>
