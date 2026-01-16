<x-filament-panels::page>
    <form wire:submit="filter">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit">
                Filter
            </x-filament::button>
        </div>
    </form>

    <div class="mt-6">
        <div class="grid gap-4 md:grid-cols-3">
            <x-filament::section>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Siswa</p>
                    <p class="text-3xl font-bold text-primary-600">{{ number_format($summary['total_siswa'] ?? 0) }}</p>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Laki-laki</p>
                    <p class="text-3xl font-bold text-info-600">{{ number_format($summary['siswa_per_jenis_kelamin']['L'] ?? 0) }}</p>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Perempuan</p>
                    <p class="text-3xl font-bold text-pink-600">{{ number_format($summary['siswa_per_jenis_kelamin']['P'] ?? 0) }}</p>
                </div>
            </x-filament::section>
        </div>

        <div class="grid gap-6 mt-6 lg:grid-cols-2">
            @if(!empty($summary['siswa_per_status']))
                <x-filament::section>
                    <x-slot name="heading">
                        Siswa per Status
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b dark:border-gray-700">
                                    <th class="py-2 px-4">Status</th>
                                    <th class="py-2 px-4 text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary['siswa_per_status'] as $status => $count)
                                    <tr class="border-b dark:border-gray-700">
                                        <td class="py-2 px-4">
                                            @switch($status)
                                                @case('aktif') <span class="text-success-600 font-semibold">Aktif</span> @break
                                                @case('alumni') <span class="text-info-600 font-semibold">Alumni</span> @break
                                                @case('pindah') <span class="text-warning-600 font-semibold">Pindah</span> @break
                                                @case('dikeluarkan') <span class="text-danger-600 font-semibold">Dikeluarkan</span> @break
                                                @case('mengundurkan_diri') <span class="text-gray-600 font-semibold">Mengundurkan Diri</span> @break
                                                @default {{ $status }}
                                            @endswitch
                                        </td>
                                        <td class="py-2 px-4 text-right font-semibold">{{ number_format($count) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            @if(!empty($summary['siswa_per_kelas']))
                <x-filament::section>
                    <x-slot name="heading">
                        Siswa per Kelas
                    </x-slot>

                    <div class="overflow-x-auto max-h-80 overflow-y-auto">
                        <table class="w-full text-left">
                            <thead class="sticky top-0 bg-white dark:bg-gray-800">
                                <tr class="border-b dark:border-gray-700">
                                    <th class="py-2 px-4">Kelas</th>
                                    <th class="py-2 px-4 text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary['siswa_per_kelas'] as $kelas => $count)
                                    <tr class="border-b dark:border-gray-700">
                                        <td class="py-2 px-4">{{ $kelas }}</td>
                                        <td class="py-2 px-4 text-right font-semibold">{{ number_format($count) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-panels::page>
