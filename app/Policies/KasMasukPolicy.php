<?php

namespace App\Policies;

use App\Models\KasMasuk;
use App\Models\User;

class KasMasukPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:KasMasuk');
    }

    public function view(User $user, KasMasuk $kasMasuk): bool
    {
        return $user->can('View:KasMasuk');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:KasMasuk');
    }

    public function update(User $user, KasMasuk $kasMasuk): bool
    {
        return $user->can('Update:KasMasuk');
    }

    public function delete(User $user, KasMasuk $kasMasuk): bool
    {
        return $user->can('Delete:KasMasuk');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:KasMasuk');
    }

    public function restore(User $user, KasMasuk $kasMasuk): bool
    {
        return $user->can('Restore:KasMasuk');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:KasMasuk');
    }

    public function forceDelete(User $user, KasMasuk $kasMasuk): bool
    {
        return $user->can('ForceDelete:KasMasuk');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:KasMasuk');
    }

    public function replicate(User $user, KasMasuk $kasMasuk): bool
    {
        return $user->can('Replicate:KasMasuk');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:KasMasuk');
    }
}
