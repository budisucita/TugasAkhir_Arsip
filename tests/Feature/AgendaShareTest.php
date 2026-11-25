<?php

namespace Tests\Feature;

use App\Models\Kegiatan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menguji keamanan fungsi berbagi (share) pada halaman agenda publik.
 *
 * Judul kegiatan yang mengandung apostrof (mis. "Jum'at"), tanda petik ganda,
 * dan karakter khusus lain tidak boleh memutus string literal JavaScript
 * sehingga menimbulkan SyntaxError di konsol peramban.
 */
class AgendaShareTest extends TestCase
{
    use RefreshDatabase;

    private function kegiatan(string $title): Kegiatan
    {
        return Kegiatan::create([
            'nama_kegiatan' => $title,
            'tanggal_mulai' => now()->toDateString(),
            'status' => 'Selesai',
            'deskripsi' => 'Deskripsi uji',
        ]);
    }

    public function test_judul_dengan_karakter_khusus_tidak_merusak_tombol_berbagi(): void
    {
        $this->kegiatan("Jum'at \"Bersama\" & Santai");

        $html = $this->get('/agenda')->assertOk()->getContent();

        // Judul dikirim lewat atribut data-title yang ter-escape HTML, sehingga
        // apostrof tidak lagi memutus string literal pada atribut onclick.
        $this->assertStringContainsString(
            'data-title="Jum&#039;at &quot;Bersama&quot; &amp; Santai"',
            $html
        );

        // onclick hanya meneruskan elemen (tanpa menyisipkan judul mentah).
        $this->assertStringContainsString('onclick="shareItem(this)"', $html);
        $this->assertStringNotContainsString("shareItem('Jum", $html);
    }

    public function test_halaman_detail_menggunakan_directive_js_untuk_judul(): void
    {
        $item = $this->kegiatan("Jum'at \"Spesial\" & <seru>");

        $html = $this->get('/agenda/'.$item->id)->assertOk()->getContent();

        // @js() meng-encode judul sebagai string JS yang aman: apostrof, kutip
        // ganda, ampersand, dan tag diubah ke escape \u sehingga tidak memutus
        // string literal.
        $this->assertStringContainsString(
            "encodeURIComponent('Jum\\u0027at \\u0022Spesial\\u0022 \\u0026 \\u003Cseru\\u003E')",
            $html
        );
    }

    public function test_berbagi_tetap_berfungsi_untuk_judul_normal(): void
    {
        $item = $this->kegiatan('Rapat Koordinasi Rutin');

        $this->get('/agenda')->assertOk()
            ->assertSee('data-title="Rapat Koordinasi Rutin"', false);

        $this->get('/agenda/'.$item->id)->assertOk()
            ->assertSee("encodeURIComponent('Rapat Koordinasi Rutin')", false);
    }
}
