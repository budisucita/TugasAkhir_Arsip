<?php

namespace Tests\Feature;

use App\Filament\Pages\PengaturanWebsite;
use App\Filament\Resources\AnggotaResource\Pages\CreateAnggota;
use App\Filament\Resources\TemplateSuratResource\Pages\CreateTemplateSurat;
use App\Models\User;
use App\Support\SecureFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menguji pengisian kolom `user_id` dengan identitas pengguna yang bertanggung
 * jawab pada operasi penyimpanan data.
 *
 * Skenario:
 * - Pembuatan Anggota lewat panel mengisi user_id.
 * - Pembuatan Template Surat lewat panel mengisi user_id.
 * - Penyimpanan Pengaturan Website mengisi user_id pada baris settings.
 */
class UserIdAutoFillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Isolasi disk agar pengujian tidak menyentuh berkas nyata.
        Storage::fake(SecureFile::diskName());
        config()->set('filesystems.default', 'public');
        Storage::fake('public');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_create_anggota_mengisi_user_id(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(CreateAnggota::class)
            ->fillForm([
                'nama' => 'Anggota Baru',
                'kontak' => '081234567890',
                'status' => 'aktif',
                'alamat' => 'Jl. Uji No. 1',
                // Memakai mime image/jpeg eksplisit (tanpa GD) agar lolos rule `image`.
                'foto' => UploadedFile::fake()->create('foto.jpg', 100, 'image/jpeg'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('anggota', [
            'nama' => 'Anggota Baru',
            'user_id' => $admin->id,
        ]);
    }

    public function test_create_template_surat_mengisi_user_id(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(CreateTemplateSurat::class)
            ->fillForm([
                'nama_dokumen' => 'Template Baru',
                'file_path' => UploadedFile::fake()->create(
                    'template.docx',
                    128,
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                ),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('template_surats', [
            'nama_dokumen' => 'Template Baru',
            'user_id' => $admin->id,
        ]);
    }

    public function test_penyimpanan_pengaturan_website_mengisi_user_id(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(PengaturanWebsite::class)
            ->fillForm(['judul_website' => 'Judul Website Uji'])
            ->call('save');

        $this->assertDatabaseHas('settings', [
            'key' => 'judul_website',
            'value' => 'Judul Website Uji',
            'user_id' => $admin->id,
        ]);
    }
}
