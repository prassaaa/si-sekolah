<?php

namespace App\Filament\Resources\Absensis\Pages;

use App\Filament\Resources\Absensis\AbsensiResource;
use App\Models\Absensi;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Siswa;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InputAbsensi extends Page
{
    protected static string $resource = AbsensiResource::class;

    protected static ?string $title = 'Input Absensi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'tanggal' => now()->format('Y-m-d'),
            'absensi' => [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Pilih Jadwal & Tanggal')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->options(fn () => Kelas::query()
                                ->whereHas('jadwalPelajarans', fn ($q) => $q->where('is_active', true))
                                ->ordered()
                                ->pluck('nama', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                $set('jadwal_pelajaran_id', null);
                                $set('absensi', []);
                            }),
                        Select::make('jadwal_pelajaran_id')
                            ->label('Jadwal Pelajaran')
                            ->options(function ($get) {
                                $kelasId = $get('kelas_id');

                                if (! $kelasId) {
                                    return [];
                                }

                                return JadwalPelajaran::query()
                                    ->where('is_active', true)
                                    ->where('kelas_id', $kelasId)
                                    ->with(['mataPelajaran', 'jamPelajaran'])
                                    ->orderBy('hari')
                                    ->orderBy('jam_pelajaran_id')
                                    ->get()
                                    ->mapWithKeys(fn (JadwalPelajaran $j) => [
                                        $j->id => $j->jadwal_lengkap,
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, $set, $get) => $this->loadSiswa($state, $get('tanggal'), $set)),
                        DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(fn ($state, $set, $get) => $this->loadSiswa($get('jadwal_pelajaran_id'), $state, $set)),
                    ])->columns(3),

                Section::make('Daftar Siswa')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Repeater::make('absensi')
                            ->label('')
                            ->schema([
                                Hidden::make('siswa_id'),
                                Hidden::make('nama_siswa')
                                    ->dehydrated(false),
                                Hidden::make('sudah_diabsen')
                                    ->dehydrated(false),
                                Hidden::make('status_tersimpan')
                                    ->dehydrated(false),
                                Placeholder::make('no')
                                    ->label('No')
                                    ->content(fn ($get): int => collect($this->data['absensi'] ?? [])
                                        ->search(fn ($item) => ($item['siswa_id'] ?? null) == $get('siswa_id')) + 1),
                                Placeholder::make('nama_siswa_display')
                                    ->label('Nama Siswa')
                                    ->content(fn ($get): string => $get('nama_siswa') ?? '-'),
                                Placeholder::make('status_absensi_siswa')
                                    ->label('Data Sebelumnya')
                                    ->content(function ($get): string {
                                        if (! $get('sudah_diabsen')) {
                                            return 'Belum diabsen';
                                        }

                                        $statusTersimpan = (string) ($get('status_tersimpan') ?? '');
                                        $statusLabel = Absensi::statusOptions()[$statusTersimpan] ?? $statusTersimpan;

                                        return "Sudah diabsen ({$statusLabel})";
                                    }),
                                Select::make('status')
                                    ->label('Status')
                                    ->options(Absensi::statusOptions())
                                    ->default('hadir')
                                    ->required()
                                    ->native(false),
                                TextInput::make('keterangan')
                                    ->label('Keterangan')
                                    ->placeholder('Opsional'),
                            ])
                            ->columns(6)
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->defaultItems(0),
                    ])
                    ->visible(fn ($get): bool => ! empty($get('absensi'))),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('inputAbsensiForm')
                ->footer($this->getFormActions())
                ->livewireSubmitHandler('simpan'),
        ]);
    }

    public function loadSiswa(?string $jadwalId, ?string $tanggal, callable $set): void
    {
        if (! $jadwalId || ! $tanggal) {
            $set('absensi', []);

            return;
        }

        $jadwal = JadwalPelajaran::find($jadwalId);

        if (! $jadwal) {
            $set('absensi', []);

            return;
        }

        $siswaList = Siswa::query()
            ->where('kelas_id', $jadwal->kelas_id)
            ->where('is_active', true)
            ->orderBy('nama')
            ->get();

        if ($siswaList->isEmpty()) {
            $set('absensi', []);

            return;
        }

        $existing = Absensi::query()
            ->where('jadwal_pelajaran_id', $jadwalId)
            ->where('tanggal', $tanggal)
            ->get()
            ->keyBy('siswa_id');

        $absensiData = [];

        foreach ($siswaList as $siswa) {
            $existingRecord = $existing->get($siswa->id);
            $absensiData[] = [
                'siswa_id' => $siswa->id,
                'nama_siswa' => $siswa->nama_lengkap,
                'sudah_diabsen' => $existingRecord !== null,
                'status_tersimpan' => $existingRecord?->status,
                'status' => $existingRecord?->status ?? 'hadir',
                'keterangan' => $existingRecord?->keterangan ?? '',
            ];
        }

        $set('absensi', $absensiData);
    }

    public function simpan(): void
    {
        $data = $this->form->getState();

        $validator = Validator::make($data, [
            'jadwal_pelajaran_id' => ['required', 'integer', 'exists:jadwal_pelajarans,id'],
            'tanggal' => ['required', 'date'],
            'absensi' => ['required', 'array', 'min:1'],
            'absensi.*.siswa_id' => ['required', 'integer', 'distinct', 'exists:siswas,id'],
            'absensi.*.status' => ['required', Rule::in(array_keys(Absensi::statusOptions()))],
            'absensi.*.keterangan' => ['nullable', 'string'],
        ]);

        $validated = $validator->validate();

        $jadwal = JadwalPelajaran::query()
            ->whereKey($validated['jadwal_pelajaran_id'])
            ->where('is_active', true)
            ->first();

        if (! $jadwal) {
            throw ValidationException::withMessages([
                'jadwal_pelajaran_id' => 'Jadwal pelajaran tidak ditemukan atau tidak aktif.',
            ]);
        }

        $submittedSiswaIds = collect($validated['absensi'])
            ->pluck('siswa_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $activeSiswaIdsInKelas = Siswa::query()
            ->where('kelas_id', $jadwal->kelas_id)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        $invalidSiswaIds = $submittedSiswaIds->diff($activeSiswaIdsInKelas)->values();

        if ($invalidSiswaIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'absensi' => 'Terdapat siswa yang tidak sesuai dengan kelas jadwal yang dipilih.',
            ]);
        }

        $missingSiswaIds = $activeSiswaIdsInKelas->diff($submittedSiswaIds)->values();

        if ($missingSiswaIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'absensi' => 'Daftar siswa belum lengkap. Muat ulang data siswa sebelum menyimpan.',
            ]);
        }

        $tanggal = Carbon::parse($validated['tanggal'])->startOfDay();

        DB::transaction(function () use ($validated, $tanggal): void {
            foreach ($validated['absensi'] as $item) {
                Absensi::updateOrCreate(
                    [
                        'jadwal_pelajaran_id' => $validated['jadwal_pelajaran_id'],
                        'siswa_id' => (int) $item['siswa_id'],
                        'tanggal' => $tanggal,
                    ],
                    [
                        'status' => $item['status'],
                        'keterangan' => filled($item['keterangan'] ?? null) ? trim((string) $item['keterangan']) : null,
                    ],
                );
            }
        });

        Notification::make()
            ->title('Absensi berhasil disimpan')
            ->body('Data absensi untuk '.count($validated['absensi']).' siswa telah disimpan.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('simpan')
                ->label('Simpan Absensi')
                ->icon('heroicon-o-check-circle')
                ->submit('inputAbsensiForm'),
        ];
    }
}
