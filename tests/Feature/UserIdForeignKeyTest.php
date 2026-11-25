<?php

namespace Tests\Feature;

use App\Models\Anggota;
use App\Models\Setting;
use App\Models\TemplateSurat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Menguji perilaku foreign key `user_id` pada tabel anggota, template_surats,
 * dan settings setelah migrasi mengubah onDelete dari `cascade` menjadi `set null`.
 *
 * Skenario yang diverifikasi:
 * - Menghapus akun pengguna TIDAK menghapus baris terkait, melainkan hanya
 *   mengosongkan kolom `user_id`-nya (NULL).
 * - Constraint foreign key `user_id` benar-benar memakai rule SET NULL.
 */
class UserIdForeignKeyTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $tables = ['anggota', 'template_surats', 'settings'];

    public function test_menghapus_user_hanya_mengosongkan_user_id(): void
    {
        $cases = [
            'anggota' => [Anggota::class, ['nama' => 'Anggota Uji']],
            'template_surats' => [TemplateSurat::class, ['nama_dokumen' => 'Template Uji', 'file_path' => 'template/uji.pdf']],
            'settings' => [Setting::class, ['key' => 'uji_'.uniqid(), 'value' => 'nilai']],
        ];

        foreach ($cases as $table => [$model, $attributes]) {
            $user = User::factory()->create();
            $row = $model::create(['user_id' => $user->id] + $attributes);
            $id = $row->getKey();

            $user->delete();

            $found = $model::find($id);

            $this->assertNotNull($found, "Baris pada tabel {$table} ikut terhapus saat user dihapus.");
            $this->assertNull($found->user_id, "Kolom user_id pada tabel {$table} tidak dikosongkan.");
        }
    }

    public function test_constraint_user_id_memakai_rule_set_null(): void
    {
        foreach ($this->tables as $table) {
            $foreignKey = collect(Schema::getForeignKeys($table))
                ->first(fn (array $fk) => in_array('user_id', $fk['columns'], true));

            $this->assertNotNull($foreignKey, "Foreign key user_id tidak ditemukan pada tabel {$table}.");
            $this->assertSame(
                'set null',
                $foreignKey['on_delete'],
                "Rule onDelete pada tabel {$table} bukan SET NULL."
            );
        }
    }
}
