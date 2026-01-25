<x-filament-panels::page>
    {{-- Filter Section --}}
    <x-filament::section icon="heroicon-o-funnel" icon-color="primary">
        <x-slot name="heading">
            Filter Data
        </x-slot>
        <x-slot name="description">
            Pilih tahun ajaran untuk melihat laporan siswa
        </x-slot>

        <form wire:submit="filter">
            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">
                    Terapkan Filter
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-filament::section>
            <div class="flex items-center gap-x-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-500/10">
                    <x-heroicon-o-users class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Siswa</p>
                    <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                        {{ number_format($summary['total_siswa'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-x-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-50 dark:bg-info-500/10">
                    <x-heroicon-o-user class="h-6 w-6 text-info-600 dark:text-info-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Laki-laki</p>
                    <p class="text-3xl font-bold text-info-600 dark:text-info-400">
                        {{ number_format($summary['siswa_per_jenis_kelamin']['L'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="flex items-center gap-x-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-pink-50 dark:bg-pink-500/10">
                    <x-heroicon-o-user class="h-6 w-6 text-pink-600 dark:text-pink-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Perempuan</p>
                    <p class="text-3xl font-bold text-pink-600 dark:text-pink-400">
                        {{ number_format($summary['siswa_per_jenis_kelamin']['P'] ?? 0) }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Tables Section --}}
    <div class="grid gap-6 lg:grid-cols-2">
        @if(!empty($summary['siswa_per_status']))
            <x-filament::section icon="heroicon-o-tag" icon-color="info">
                <x-slot name="heading">
                    Siswa per Status
                </x-slot>

                <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                    Status
                                </th>
                                <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">
                                    Jumlah
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                            @foreach($summary['siswa_per_status'] as $status => $count)
                                <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="fi-ta-cell px-4 py-3 text-sm text-gray-950 dark:text-white">
                                        @switch($status)
                                            @case('aktif')
                                                <x-filament::badge color="success">Aktif</x-filament::badge>
                                                @break
                                            @case('alumni')
                                                <x-filament::badge color="info">Alumni</x-filament::badge>
                                                @break
                                            @case('pindah')
                                                <x-filament::badge color="warning">Pindah</x-filament::badge>
                                                @break
                                            @case('dikeluarkan')
                                                <x-filament::badge color="danger">Dikeluarkan</x-filament::badge>
                                                @break
                                            @case('mengundurkan_diri')
                                                <x-filament::badge color="gray">Mengundurkan Diri</x-filament::badge>
                                                @break
                                            @default
                                                <x-filament::badge>{{ ucfirst($status) }}</x-filament::badge>
                                        @endswitch
                                    </td>
                                    <td class="fi-ta-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ number_format($count) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        @if(!empty($summary['siswa_per_kelas']))
            <x-filament::section icon="heroicon-o-academic-cap" icon-color="success">
                <x-slot name="heading">
                    Siswa per Kelas
                </x-slot>

                <div class="fi-ta-content relative divide-y divide-gray-200 overflow-x-auto dark:divide-white/10 max-h-80 overflow-y-auto">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                        <thead class="bg-gray-50 dark:bg-white/5 sticky top-0">
                            <tr>
                                <th class="fi-ta-header-cell px-4 py-3 text-start text-sm font-semibold text-gray-950 dark:text-white">
                                    Kelas
                                </th>
                                <th class="fi-ta-header-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">
                                    Jumlah
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                            @foreach($summary['siswa_per_kelas'] as $kelas => $count)
                                <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                    <td class="fi-ta-cell px-4 py-3 text-sm text-gray-950 dark:text-white">
                                        {{ $kelas }}
                                    </td>
                                    <td class="fi-ta-cell px-4 py-3 text-end text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ number_format($count) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
