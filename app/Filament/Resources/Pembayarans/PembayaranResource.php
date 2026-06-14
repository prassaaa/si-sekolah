<?php

namespace App\Filament\Resources\Pembayarans;

use App\Filament\Resources\Pembayarans\Pages\CreatePembayaran;
use App\Filament\Resources\Pembayarans\Pages\EditPembayaran;
use App\Filament\Resources\Pembayarans\Pages\ListPembayarans;
use App\Filament\Resources\Pembayarans\Pages\ViewPembayaran;
use App\Filament\Resources\Pembayarans\Schemas\PembayaranForm;
use App\Filament\Resources\Pembayarans\Schemas\PembayaranInfolist;
use App\Filament\Resources\Pembayarans\Tables\PembayaransTable;
use App\Models\Pembayaran;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PembayaranResource extends Resource
{
    protected static ?string $model = Pembayaran::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Pembayaran';

    protected static ?string $modelLabel = 'Pembayaran';

    protected static ?string $pluralModelLabel = 'Pembayaran';

    protected static UnitEnum|string|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'nomor_transaksi';

    /**
     * Kolom/relasi untuk pencarian global (dot bersarang 2 level didukung Filament via whereHas).
     *
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['nomor_transaksi', 'tagihanSiswa.siswa.nama', 'tagihanSiswa.siswa.nis'];
    }

    /**
     * Detail yang ditampilkan di hasil pencarian global.
     *
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Siswa' => $record->tagihanSiswa?->siswa?->nama ?? '-',
        ];
    }

    /**
     * Query dasar dengan eager-load relasi bersarang agar tidak terjadi N+1.
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('tagihanSiswa.siswa');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() > 0 ? 'warning' : 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return PembayaranForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PembayaranInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PembayaransTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPembayarans::route('/'),
            'create' => CreatePembayaran::route('/create'),
            'view' => ViewPembayaran::route('/{record}'),
            'edit' => EditPembayaran::route('/{record}/edit'),
        ];
    }
}
