<?php

namespace App\Policies;

use App\Models\PeriodeAkuntansi;
use App\Models\User;

class PeriodeAkuntansiPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:PeriodeAkuntansi');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PeriodeAkuntansi $periodeAkuntansi): bool
    {
        return $user->can('View:PeriodeAkuntansi');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create:PeriodeAkuntansi');
    }

    /**
     * Determine whether the user can update the model.
     *
     * Membuka kembali periode yang sudah ditutup (closed → open) hanya boleh
     * dilakukan super_admin. Selama periode masih terbuka, pengeditan biasa
     * cukup dengan permission Update:PeriodeAkuntansi.
     */
    public function update(User $user, PeriodeAkuntansi $periodeAkuntansi): bool
    {
        if ($periodeAkuntansi->isTertutup()) {
            return $this->reopen($user, $periodeAkuntansi);
        }

        return $user->can('Update:PeriodeAkuntansi');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PeriodeAkuntansi $periodeAkuntansi): bool
    {
        return $user->can('Delete:PeriodeAkuntansi');
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:PeriodeAkuntansi');
    }

    /**
     * Determine whether the user can close (tutup) a periode.
     *
     * Menutup periode memerlukan permission Update:PeriodeAkuntansi dan periode
     * harus masih terbuka.
     */
    public function tutup(User $user, PeriodeAkuntansi $periodeAkuntansi): bool
    {
        return $user->can('Update:PeriodeAkuntansi') && ! $periodeAkuntansi->isTertutup();
    }

    /**
     * Determine whether the user can reopen (buka kembali) a closed periode.
     *
     * Hanya super_admin yang boleh membuka kembali periode tertutup agar
     * laporan yang telah diserahkan tidak diubah sembarangan.
     */
    public function reopen(User $user, PeriodeAkuntansi $periodeAkuntansi): bool
    {
        return $periodeAkuntansi->isTertutup() && $user->hasRole('super_admin');
    }
}
