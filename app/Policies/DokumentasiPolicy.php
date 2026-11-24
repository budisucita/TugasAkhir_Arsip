<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Dokumentasi;
use Illuminate\Auth\Access\HandlesAuthorization;

class DokumentasiPolicy
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
    public function view(User $user, Dokumentasi $dokumentasi): bool
    {
        return in_array($user->role, ['admin', 'pengurus']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'admin';
        //return $user->role === 'admin' || $user->role === 'pengurus';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Dokumentasi $dokumentasi): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Dokumentasi $dokumentasi): bool
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
    public function forceDelete(User $user, Dokumentasi $dokumentasi): bool
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
    public function restore(User $user, Dokumentasi $dokumentasi): bool
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
    public function replicate(User $user, Dokumentasi $dokumentasi): bool
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
