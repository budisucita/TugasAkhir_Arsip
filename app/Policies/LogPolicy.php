<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Log;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Kebijakan audit trail: catatan log bersifat permanen (append-only).
 *
 * Hanya operasi baca (viewAny/view) yang diizinkan. Seluruh operasi yang dapat
 * membuat, mengubah, menggandakan, atau menghapus entri log diblokir untuk
 * semua peran (termasuk admin) demi menjaga integritas dan keandalan jejak
 * audit sistem.
 */
class LogPolicy
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
    public function view(User $user, Log $log): bool
    {
        return in_array($user->role, ['admin', 'pengurus']);
    }

    /**
     * Determine whether the user can create models.
     * Entri log hanya dibuat otomatis oleh sistem (Observer), bukan manual.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Log $log): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Log $log): bool
    {
        return false;
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Log $log): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Log $log): bool
    {
        return false;
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Log $log): bool
    {
        return false;
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return false;
    }
}
