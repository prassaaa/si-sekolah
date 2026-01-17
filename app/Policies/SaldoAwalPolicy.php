<?php

namespace App\Policies;

use App\Models\SaldoAwal;
use App\Models\User;

class SaldoAwalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:SaldoAwal');
    }

    public function view(User $user, SaldoAwal $saldoAwal): bool
    {
        return $user->can('View:SaldoAwal');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:SaldoAwal');
    }

    public function update(User $user, SaldoAwal $saldoAwal): bool
    {
        return $user->can('Update:SaldoAwal');
    }

    public function delete(User $user, SaldoAwal $saldoAwal): bool
    {
        return $user->can('Delete:SaldoAwal');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:SaldoAwal');
    }

    public function restore(User $user, SaldoAwal $saldoAwal): bool
    {
        return $user->can('Restore:SaldoAwal');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:SaldoAwal');
    }

    public function forceDelete(User $user, SaldoAwal $saldoAwal): bool
    {
        return $user->can('ForceDelete:SaldoAwal');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:SaldoAwal');
    }

    public function replicate(User $user, SaldoAwal $saldoAwal): bool
    {
        return $user->can('Replicate:SaldoAwal');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:SaldoAwal');
    }
}
