<?php

namespace App\Policies;

use App\Models\RfidScanLog;
use App\Models\User;

class RfidScanLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:RfidScanLog');
    }

    public function view(User $user, RfidScanLog $rfidScanLog): bool
    {
        return $user->can('View:RfidScanLog');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, RfidScanLog $rfidScanLog): bool
    {
        return false;
    }

    public function delete(User $user, RfidScanLog $rfidScanLog): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, RfidScanLog $rfidScanLog): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
