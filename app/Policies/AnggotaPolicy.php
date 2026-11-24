<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Anggota;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnggotaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'pengurus']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Anggota $anggota): bool
    {
        return in_array($user->role, ['admin', 'pengurus']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Anggota $anggota): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Anggota $anggota): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Anggota $anggota): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Anggota $anggota): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Anggota $anggota): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->role === 'admin';
    }
}
