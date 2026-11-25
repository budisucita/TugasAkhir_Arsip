<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel yang memiliki foreign key `user_id` dengan perilaku cascadeOnDelete().
     *
     * @var array<int, string>
     */
    private array $tables = ['anggota', 'template_surats', 'settings'];

    /**
     * Ubah opsi onDelete constraint `user_id` dari `cascade` menjadi `set null`.
     *
     * Dengan begitu, ketika akun pengguna dihapus, baris pada tabel terkait tetap
     * ada dan hanya kolom `user_id`-nya yang dikosongkan (NULL) sehingga tidak ada
     * data historis yang hilang. Perubahan constraint tidak menyentuh data yang
     * sudah ada, jadi aman dijalankan pada database berisi data.
     */
    public function up(): void
    {
        foreach ($this->tables as $table) {
            // Lepas foreign key lama (onDelete: cascade).
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['user_id']);
            });

            // Pasang kembali foreign key dengan onDelete: set null.
            // Kolom sudah nullable + terindeks, jadi tidak perlu diubah lagi.
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Kembalikan constraint `user_id` ke perilaku semula (onDelete: cascade).
     */
    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['user_id']);
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();
            });
        }
    }
};
