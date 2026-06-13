<?php

namespace App\Policies;

use App\Models\JurnalUmum;
use App\Models\User;

class JurnalUmumPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:JurnalUmum');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, JurnalUmum $jurnalUmum): bool
    {
        return $user->can('View:JurnalUmum');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('Create:JurnalUmum');
    }

    /**
     * Determine whether the user can update the model.
     *
     * Jurnal hasil posting otomatis (jenis_referensi terisi) hanya boleh
     * diubah lewat dokumen sumbernya, bukan langsung dari UI.
     */
    public function update(User $user, JurnalUmum $jurnalUmum): bool
    {
        return $user->can('Update:JurnalUmum') && ! $jurnalUmum->isAutoPosted();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Jurnal hasil posting otomatis tidak boleh dihapus secara manual
     * agar pasangan debit/kredit tidak rusak.
     */
    public function delete(User $user, JurnalUmum $jurnalUmum): bool
    {
        return $user->can('Delete:JurnalUmum') && ! $jurnalUmum->isAutoPosted();
    }

    /**
     * Determine whether the user can delete any models.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:JurnalUmum');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, JurnalUmum $jurnalUmum): bool
    {
        return $user->can('Restore:JurnalUmum');
    }

    /**
     * Determine whether the user can restore any models.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:JurnalUmum');
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * Jurnal hasil posting otomatis tidak boleh dihapus permanen secara manual.
     */
    public function forceDelete(User $user, JurnalUmum $jurnalUmum): bool
    {
        return $user->can('ForceDelete:JurnalUmum') && ! $jurnalUmum->isAutoPosted();
    }

    /**
     * Determine whether the user can permanently delete any models.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:JurnalUmum');
    }

    /**
     * Determine whether the user can replicate the model.
     */
    public function replicate(User $user, JurnalUmum $jurnalUmum): bool
    {
        return $user->can('Replicate:JurnalUmum');
    }

    /**
     * Determine whether the user can reorder models.
     */
    public function reorder(User $user): bool
    {
        return $user->can('Reorder:JurnalUmum');
    }
}
