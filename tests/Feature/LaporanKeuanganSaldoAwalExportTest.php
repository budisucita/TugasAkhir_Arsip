<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use App\Models\Keuangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji perhitungan Saldo Awal Periode dan ekspor Excel laporan keuangan.
 *
 * Fokus:
 * - Saldo Awal = akumulasi (pemasukan - pengeluaran) transaksi SEBELUM
 *   tanggal_mulai, bukan selalu dimulai dari Rp 0.
 * - Saldo awal menghormati filter kegiatan.
 * - Tanpa filter tanggal, laporan tidak menampilkan baris saldo awal.
 * - Ekspor menghasilkan berkas .xlsx valid (arsip ZIP/OpenXML).
 */
class LaporanKeuanganSaldoAwalExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_saldo_awal_menghitung_akumulasi_sebelum_periode(): void
    {
        $this->makeKeuangan('Sumbangan Awal', '2026-01-05', 'pemasukan', 500000);
        $this->makeKeuangan('Belanja Awal', '2026-01-10', 'pengeluaran', 200000);
        $this->makeKeuangan('Belanja Februari', '2026-02-10', 'pengeluaran', 50000);

        $response = $this->actingAs($this->admin())
            ->get('/laporan/keuangan/print?tanggal_mulai=2026-02-01&tanggal_selesai=2026-02-28');

        $response->assertOk();
        $response->assertSee('Saldo Awal Periode');
        // 500.000 - 200.000 = 300.000 (akumulasi sebelum 1 Februari).
        $response->assertSee('300.000');
        $response->assertSee('Belanja Februari');
        // Transaksi sebelum periode tidak masuk sebagai baris data.
        $response->assertDontSee('Sumbangan Awal');
        $response->assertDontSee('Belanja Awal');
    }

    public function test_tanpa_filter_tanggal_tidak_menampilkan_saldo_awal(): void
    {
        $this->makeKeuangan('Transaksi Februari', '2026-02-10', 'pemasukan', 100000);

        $this->actingAs($this->admin())
            ->get('/laporan/keuangan/print')
            ->assertOk()
            ->assertDontSee('Saldo Awal Periode');
    }

    public function test_saldo_awal_menghormati_filter_kegiatan(): void
    {
        $kegiatanA = $this->makeKegiatan('Kegiatan A');
        $kegiatanB = $this->makeKegiatan('Kegiatan B');

        $this->makeKeuangan('Masuk A', '2026-01-05', 'pemasukan', 1000000, $kegiatanA->id);
        $this->makeKeuangan('Keluar B', '2026-01-06', 'pengeluaran', 400000, $kegiatanB->id);
        $this->makeKeuangan('Keluar A Februari', '2026-02-06', 'pengeluaran', 100000, $kegiatanA->id);

        $response = $this->actingAs($this->admin())
            ->get('/laporan/keuangan/print?kegiatan_id=' . $kegiatanA->id . '&tanggal_mulai=2026-02-01');

        $response->assertOk();
        $response->assertSee('Saldo Awal Periode');
        // Saldo awal hanya menghitung transaksi kegiatan A.
        $response->assertSee('1.000.000');
        $response->assertDontSee('Masuk A');
    }

    public function test_admin_dapat_mengekspor_laporan_excel(): void
    {
        $this->makeKeuangan('Belanja', '2026-02-10', 'pengeluaran', 50000);

        $response = $this->actingAs($this->admin())->get('/laporan/keuangan/export');

        $response->assertOk();
        $response->assertDownload();

        $this->assertStringContainsString(
            'spreadsheetml.sheet',
            (string) $response->headers->get('content-type')
        );
        $this->assertStringContainsString('.xlsx', (string) $response->headers->get('content-disposition'));

        // Berkas .xlsx adalah arsip ZIP (OpenXML) -> diawali signature "PK".
        $path = $response->baseResponse->getFile()->getPathname();
        $this->assertStringStartsWith('PK', (string) file_get_contents($path));
    }

    public function test_tamu_tidak_dapat_mengekspor_laporan(): void
    {
        $this->get('/laporan/keuangan/export')
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_pengguna_tanpa_izin_tidak_dapat_mengekspor_laporan(): void
    {
        $this->actingAs(User::factory()->make(['role' => 'tamu']))
            ->get('/laporan/keuangan/export')
            ->assertForbidden();
    }

    public function test_ekspor_dengan_tanggal_tidak_valid_menghasilkan_error_validasi(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/keuangans')
            ->get('/laporan/keuangan/export?tanggal_mulai=bukan-tanggal')
            ->assertRedirect('/admin/keuangans')
            ->assertSessionHasErrors('tanggal_mulai');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeKegiatan(string $nama): Kegiatan
    {
        return Kegiatan::create([
            'nama_kegiatan' => $nama,
            'tanggal_mulai' => '2026-01-01',
        ]);
    }

    private function makeKeuangan(
        string $keterangan,
        string $tanggal,
        string $jenis = 'pengeluaran',
        float $jumlah = 1000,
        ?int $kegiatanId = null
    ): Keuangan {
        return Keuangan::create([
            'jenis_transaksi' => $jenis,
            'jumlah' => $jumlah,
            'keterangan' => $keterangan,
            'tanggal' => $tanggal,
            'kegiatan_id' => $kegiatanId,
        ]);
    }
}
