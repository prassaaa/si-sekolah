<?php

namespace App\Policies;

use App\Models\SettingGaji;
use App\Models\User;

class SettingGajiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:SettingGaji');
    }

    public function view(User $user, SettingGaji $settingGaji): bool
    {
        return $user->can('View:SettingGaji');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:SettingGaji');
    }

    public function update(User $user, SettingGaji $settingGaji): bool
    {
        return $user->can('Update:SettingGaji');
    }

    public function delete(User $user, SettingGaji $settingGaji): bool
    {
        return $user->can('Delete:SettingGaji');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('DeleteAny:SettingGaji');
    }

    public function restore(User $user, SettingGaji $settingGaji): bool
    {
        return $user->can('Restore:SettingGaji');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('RestoreAny:SettingGaji');
    }

    public function forceDelete(User $user, SettingGaji $settingGaji): bool
    {
        return $user->can('ForceDelete:SettingGaji');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('ForceDeleteAny:SettingGaji');
    }

    public function replicate(User $user, SettingGaji $settingGaji): bool
    {
        return $user->can('Replicate:SettingGaji');
    }

    public function reorder(User $user): bool
    {
        return $user->can('Reorder:SettingGaji');
    }
}
