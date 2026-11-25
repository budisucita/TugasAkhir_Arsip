<?php

namespace Tests\Feature;

use App\Models\Keuangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji keamanan (otorisasi Policy) dan penanganan error pada rute cetak
 * laporan keuangan.
 *
 * Skenario:
 * - Tamu diarahkan ke halaman login.
 * - Pengguna login tanpa izin mendapatkan 403 Forbidden.
 * - Admin/pengurus dapat mengakses laporan.
 * - Parameter query tidak valid menghasilkan error validasi (bukan 500).
 * - Parameter valid tetap menghasilkan laporan yang benar.
 */
class LaporanKeuanganPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/laporan/keuangan/print')
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_pengguna_tanpa_izin_ditolak_403(): void
    {
        // Peran di luar admin/pengurus (kolom role tidak mengizinkan 'tamu'
        // tersimpan) dilarang Policy meskipun sudah login.
        $this->actingAs(User::factory()->make(['role' => 'tamu']))
            ->get('/laporan/keuangan/print')
            ->assertForbidden();
    }

    public function test_admin_dapat_mengakses_laporan(): void
    {
        $this->actingAs($this->admin())
            ->get('/laporan/keuangan/print')
            ->assertOk()
            ->assertSee('LAPORAN KEUANGAN');
    }

    public function test_pengurus_dapat_mengakses_laporan(): void
    {
        $this->actingAs($this->pengurus())
            ->get('/laporan/keuangan/print')
            ->assertOk();
    }

    public function test_tanggal_tidak_valid_menghasilkan_error_validasi_bukan_500(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/keuangans')
            ->get('/laporan/keuangan/print?tanggal_mulai=bukan-tanggal')
            ->assertRedirect('/admin/keuangans')
            ->assertSessionHasErrors('tanggal_mulai');
    }

    public function test_jenis_transaksi_tidak_dikenal_menghasilkan_error_validasi(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/keuangans')
            ->get('/laporan/keuangan/print?jenis_transaksi=transfer')
            ->assertRedirect('/admin/keuangans')
            ->assertSessionHasErrors('jenis_transaksi');
    }

    public function test_tempat_tidak_valid_menghasilkan_error_validasi(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/keuangans')
            ->get('/laporan/keuangan/print?tempat='.urlencode('<script>alert(1)</script>'))
            ->assertRedirect('/admin/keuangans')
            ->assertSessionHasErrors('tempat');
    }

    public function test_kegiatan_id_tidak_ada_menghasilkan_error_validasi(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/keuangans')
            ->get('/laporan/keuangan/print?kegiatan_id=999999')
            ->assertRedirect('/admin/keuangans')
            ->assertSessionHasErrors('kegiatan_id');
    }

    public function test_parameter_valid_menghasilkan_laporan_terfilter(): void
    {
        $this->makeKeuangan('Pembelian ATK', '2026-01-10');
        $this->makeKeuangan('Sewa Tenda', '2026-02-15');
        $this->makeKeuangan('Konsumsi Rapat', '2026-03-20');

        $response = $this->actingAs($this->admin())
            ->get('/laporan/keuangan/print?tanggal_mulai=2026-02-01&tanggal_selesai=2026-02-28&tempat=Denpasar');

        $response->assertOk();
        $response->assertSee('Sewa Tenda');
        $response->assertDontSee('Pembelian ATK');
        $response->assertDontSee('Konsumsi Rapat');
        $response->assertSee('Denpasar');
    }

    public function test_filter_jenis_transaksi_berfungsi(): void
    {
        $this->makeKeuangan('Donasi Warga', '2026-01-05', 'pemasukan');
        $this->makeKeuangan('Belanja Konsumsi', '2026-01-06', 'pengeluaran');

        $response = $this->actingAs($this->admin())
            ->get('/laporan/keuangan/print?jenis_transaksi=pemasukan');

        $response->assertOk();
        $response->assertSee('Donasi Warga');
        $response->assertDontSee('Belanja Konsumsi');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function pengurus(): User
    {
        return User::factory()->create(['role' => 'pengurus']);
    }

    private function makeKeuangan(string $keterangan, string $tanggal, string $jenis = 'pengeluaran', float $jumlah = 1000): Keuangan
    {
        return Keuangan::create([
            'jenis_transaksi' => $jenis,
            'jumlah' => $jumlah,
            'keterangan' => $keterangan,
            'tanggal' => $tanggal,
        ]);
    }
}
