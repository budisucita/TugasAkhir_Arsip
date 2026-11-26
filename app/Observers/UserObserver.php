<?php

namespace App\Observers;

use App\Models\Log;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as SysLog;

/**
 * Mencatat seluruh perubahan akun pengguna, dengan penekanan pada perubahan
 * peran (role) ke dalam tabel logs agar jejak audit otorisasi lengkap.
 */
class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->writeLog(
            $user,
            'created',
            "Menambahkan akun pengguna \"{$user->name}\" dengan peran {$user->role}",
            null,
            $this->snapshot($user)
        );
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $perubahan = [];

        if ($user->isDirty('role')) {
            $perubahan[] = "Peran ({$user->getOriginal('role')} -> {$user->role})";
        }

        if ($user->isDirty('name')) {
            $perubahan[] = 'Nama diperbarui';
        }

        if ($user->isDirty('email')) {
            $perubahan[] = 'Email diperbarui';
        }

        if ($user->isDirty('password')) {
            $perubahan[] = 'Password diperbarui';
        }

        // Tanpa perubahan berarti (mis. menyimpan form tanpa mengubah apa pun),
        // tidak perlu membuat entri log.
        if (empty($perubahan)) {
            return;
        }

        $deskripsi = 'Mengubah akun "' . $user->name . '": ' . implode(', ', $perubahan);

        $this->writeLog(
            $user,
            'updated',
            $deskripsi,
            $this->snapshotFromAttributes($user->getOriginal()),
            $this->snapshot($user)
        );
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $this->writeLog(
            $user,
            'deleted',
            "Menghapus akun pengguna \"{$user->name}\" (peran {$user->role})",
            $this->snapshot($user),
            null
        );
    }

    /**
     * Tulis entri log audit tanpa mengganggu alur utama bila terjadi kegagalan.
     */
    private function writeLog(User $user, string $action, string $description, ?array $before, ?array $after): void
    {
        try {
            Log::create([
                'user_id' => Auth::id(),
                'model' => User::class,
                'record_id' => $user->id,
                'action' => $action,
                'description' => $description,
                'before' => $before,
                'after' => $after,
            ]);
        } catch (\Throwable $e) {
            SysLog::error('Gagal menulis log pengguna (' . $action . ')', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? null,
            ]);
        }
    }

    /**
     * Snapshot data user tanpa kolom sensitif (password, token).
     */
    private function snapshot(User $user): array
    {
        return $this->snapshotFromAttributes($user->getAttributes());
    }

    /**
     * Bersihkan kolom sensitif dari array atribut sebelum disimpan ke log.
     */
    private function snapshotFromAttributes(array $attributes): array
    {
        unset($attributes['password'], $attributes['remember_token']);

        return $attributes;
    }
}
